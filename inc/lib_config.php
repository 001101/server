<?php
class OC_CONFIG{
   /**
   * show the configform
   *
   */
  public static function showconfigform(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    global $CONFIG_DATADIRECTORY;
    global $CONFIG_HTTPFORCESSL;
    global $CONFIG_DATEFORMAT;
    global $CONFIG_DBNAME;
    oc_require('templates/configform.php');
  }
  
  /**
   * show the configform
   *
   */
  public static function showadminform(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    global $CONFIG_DATADIRECTORY;
    global $CONFIG_HTTPFORCESSL;
    global $CONFIG_DATEFORMAT;
    global $CONFIG_DBNAME;
    global $CONFIG_INSTALLED;
		$allow=false;
		if(!$CONFIG_INSTALLED){
			$allow=true;
		}elseif(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				$allow=true;
			}
		}
    if($allow){
		oc_require('templates/adminform.php');
	}
  }

	public static function createuserlisener(){
		if(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				if(isset($_POST['new_username']) and isset($_POST['new_password'])){
					if(OC_USER::createuser($_POST['new_username'],$_POST['new_password'])){
						return 'user successfully created';
					}else{
						return 'error while trying to create user';
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
	}
	
	public static function creategrouplisener(){
		if(OC_USER::isLoggedIn()){
			if(isset($_POST['creategroup']) and $_POST['creategroup']==1){
				if(OC_USER::creategroup($_POST['groupname'])){
					if(OC_USER::addtogroup($_SESSION['username'],$_POST['groupname'])){
						return 'group successfully created';
					}else{
						return 'error while trying to add user to the new created group';
					}
				}else{
					return 'error while trying to create group';
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	
	/**
   * lisen for configuration changes
   *
   */
	public static function configlisener(){
		if(OC_USER::isLoggedIn()){
			if(isset($_POST['config']) and $_POST['config']==1){
				$error='';
				if(!OC_USER::checkpassword($_SESSION['username'],$_POST['currentpassword'])){
					$error.='wrong password<br />';
				}else{
					if(isset($_POST['changepass']) and $_POST['changepass']==1){
						if(!isset($_POST['password'])     or empty($_POST['password']))     $error.='password not set<br />';
						if(!isset($_POST['password2'])    or empty($_POST['password2']))    $error.='retype password not set<br />';
						if($_POST['password']<>$_POST['password2'] )                        $error.='passwords are not the same<br />';
						if(empty($error)){
							if(!OC_USER::setpassword($_SESSION['username'],$_POST['password'])){
								$error.='error while trying to set password<br />';
							}
						}
					}
				}
				return $error;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	
	/**
	* lisen for admin configuration changes and write it to the file
	*4bd0be1185e76
	*/
	public static function writeadminlisener(){
		global $CONFIG_INSTALLED;
		$allow=false;
		if(!$CONFIG_INSTALLED){
			$allow=true;
		}elseif(OC_USER::isLoggedIn()){
			if(OC_USER::ingroup($_SESSION['username'],'admin')){
				$allow=true;
			}
		}
		if($allow){
			global $DOCUMENTROOT;
			global $SERVERROOT;
			global $WEBROOT;
			global $CONFIG_DBHOST;
			global $CONFIG_DBNAME;
			global $CONFIG_INSTALLED;
			global $CONFIG_DBUSER;
			global $CONFIG_DBPASSWORD;
			global $CONFIG_DBTYPE;
			global $CONFIG_DBTABLEPREFIX;
			global $CONFIG_ADMINLOGIN;
			global $CONFIG_ADMINPASSWORD;
			if(isset($_POST['set_config'])){
				
				//checkdata
				$error='';
				$FIRSTRUN=!$CONFIG_INSTALLED;
				if(!$FIRSTRUN){
					if(!OC_USER::login($_SESSION['username'],$_POST['currentpassword'])){
					$error.='wrong password<br />';
					}
				}
				
				if((!isset($_POST['adminlogin'])        or empty($_POST['adminlogin'])) and $FIRSTRUN)        $error.='admin login not set<br />';
				if((!isset($_POST['adminpassword'])     or empty($_POST['adminpassword'])) and $FIRSTRUN)     $error.='admin password not set<br />';
				if((!isset($_POST['adminpassword2'])    or empty($_POST['adminpassword2'])) and $FIRSTRUN)    $error.='retype admin password not set<br />';
				if(!isset($_POST['datadirectory'])     or empty($_POST['datadirectory']))     $error.='data directory not set<br />';
				if(!isset($_POST['dateformat'])        or empty($_POST['dateformat']))        $error.='dateformat not set<br />';
				if(!isset($_POST['dbname'])            or empty($_POST['dbname']))            $error.='databasename not set<br />';
				if($FIRSTRUN and $_POST['adminpassword']<>$_POST['adminpassword2'] )                        $error.='admin passwords are not the same<br />';
				$dbtype=$_POST['dbtype'];
				if($dbtype=='mysql'){
					if(!isset($_POST['dbhost'])            or empty($_POST['dbhost']))            $error.='database host not set<br />';
					if(!isset($_POST['dbuser'])            or empty($_POST['dbuser']))            $error.='database user not set<br />';
					if($_POST['dbpassword']<>$_POST['dbpassword2'] )                        $error.='database passwords are not the same<br />';
					
				}
				if(isset($_POST['enablebackup']) and $_POST['enablebackup']==1){
					if(!isset($_POST['backupdirectory'])     or empty($_POST['backupdirectory']))     $error.='backup directory not set<br />';
				}
				if(!$FIRSTRUN){
					if(!isset($_POST['adminpassword']) or empty($_POST['adminpassword'])){
						$_POST['adminpassword']=$CONFIG_ADMINPASSWORD;
					}
					if(!isset($_POST['dbpassword']) or empty($_POST['dbpassword'])){
						$_POST['dbpassword']=$CONFIG_DBPASSWORD;
					}
				}
				if(!is_dir($_POST['datadirectory'])){
					try{
						mkdir($_POST['datadirectory']);
					}catch(Exception $e){
						$error.='error while trying to create data directory<br/>';
					}
				}
				if(empty($error)) {
					if($CONFIG_DBTYPE!=$dbtype or $FIRSTRUN){
						//create/fill database
						$CONFIG_DBTYPE=$dbtype;
						$CONFIG_DBNAME=$_POST['dbname'];
						if($dbtype=='mysql'){
							$CONFIG_DBHOST=$_POST['dbhost'];
							$CONFIG_DBUSER=$_POST['dbuser'];
							$CONFIG_DBPASSWORD=$_POST['dbpassword'];
						}
						try{
							if(isset($_POST['createdatabase']) and $CONFIG_DBTYPE=='mysql'){
								self::createdatabase($_POST['dbadminuser'],$_POST['dbadminpwd']);
							}
						}catch(Exception $e){
							$error.='error while trying to create the database<br/>';
						}
						if($CONFIG_DBTYPE=='sqlite'){
							$f=@fopen($SERVERROOT.'/'.$CONFIG_DBNAME,'a+');
							if(!$f){
								$error.='path of sqlite database not writable by server<br/>';
							}
							OC_DB::disconnect();
							unlink($SERVERROOT.'/'.$CONFIG_DBNAME);
						}
						try{
							if(isset($_POST['filldb'])){
								self::filldatabase();
							}
						}catch(Exception $e){
							echo 'testin';
							$error.='error while trying to fill the database<br/>';
						}
						if($CONFIG_DBTYPE=='sqlite'){
							OC_DB::disconnect();
						}
					}
					if($FIRSTRUN){
						if(!OC_USER::createuser($_POST['adminlogin'],$_POST['adminpassword']) && !OC_USER::login($_POST['adminlogin'],$_POST['adminpassword'])){
							$error.='error while trying to create the admin user<br/>';
						}
						if(OC_USER::getgroupid('admin')==0){
							if(!OC_USER::creategroup('admin')){
								$error.='error while trying to create the admin group<br/>';
							}
						}
						if(!OC_USER::addtogroup($_POST['adminlogin'],'admin')){
							$error.='error while trying to add the admin user to the admin group<br/>';
						}
					}
					//storedata
					$config='<?php '."\n";
					$config.='$CONFIG_INSTALLED=true;'."\n";
					$config.='$CONFIG_DATADIRECTORY=\''.$_POST['datadirectory']."';\n";
					if(isset($_POST['forcessl'])) $config.='$CONFIG_HTTPFORCESSL=true'.";\n"; else $config.='$CONFIG_HTTPFORCESSL=false'.";\n";
					if(isset($_POST['enablebackup'])) $config.='$CONFIG_ENABLEBACKUP=true'.";\n"; else $config.='$CONFIG_ENABLEBACKUP=false'.";\n";
					if(isset($_POST['enablebackup']) and $_POST['enablebackup']==1){
						$config.='$CONFIG_BACKUPDIRECTORY=\''.$_POST['backupdirectory']."';\n";
					}
					$config.='$CONFIG_DATEFORMAT=\''.$_POST['dateformat']."';\n";
					$config.='$CONFIG_DBTYPE=\''.$dbtype."';\n";
					$config.='$CONFIG_DBNAME=\''.$_POST['dbname']."';\n";
					if($dbtype=='mysql'){
						$config.='$CONFIG_DBHOST=\''.$_POST['dbhost']."';\n";
						$config.='$CONFIG_DBUSER=\''.$_POST['dbuser']."';\n";
						$config.='$CONFIG_DBPASSWORD=\''.$_POST['dbpassword']."';\n";
					}
					$config.='?> ';

					$filename=$SERVERROOT.'/config/config.php';
					if(empty($error)){
						header("Location: ".$WEBROOT."/");
						try{
							file_put_contents($filename,$config);
						}catch(Exception $e){
							$error.='error while trying to save the configuration file<br/>';
							return $error;
						}
					}else{
						return $error;
					}

				}
				return($error);

			}
		}
	}
  
   /**
   * Fills the database with the initial tables
   * Note: while the AUTO_INCREMENT function is not supported by SQLite
   *    the same effect can be achieved by accessing the SQLite pseudo-column
   *    "rowid"
   */
   private static function filldatabase(){
		global $CONFIG_DBTYPE;
      if($CONFIG_DBTYPE=='sqlite'){
        $query="CREATE TABLE 'locks' (
  'token' VARCHAR(255) NOT NULL DEFAULT '',
  'path' varchar(200) NOT NULL DEFAULT '',
  'created' int(11) NOT NULL DEFAULT '0',
  'modified' int(11) NOT NULL DEFAULT '0',
  'expires' int(11) NOT NULL DEFAULT '0',
  'owner' varchar(200) DEFAULT NULL,
  'recursive' int(11) DEFAULT '0',
  'writelock' int(11) DEFAULT '0',
  'exclusivelock' int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ('token'),
  UNIQUE ('token')
 );

CREATE TABLE 'log' (
  `id` INTEGER ASC DEFAULT '' NOT NULL,
  'timestamp' int(11) NOT NULL,
  'user' varchar(250) NOT NULL,
  'type' int(11) NOT NULL,
  'message' varchar(250) NOT NULL,
  PRIMARY KEY ('id')
);


CREATE TABLE  'properties' (
  'path' varchar(255) NOT NULL DEFAULT '',
  'name' varchar(120) NOT NULL DEFAULT '',
  'ns' varchar(120) NOT NULL DEFAULT 'DAV:',
  'value' text,
  PRIMARY KEY ('path','name','ns')
);

CREATE TABLE 'users' (
  'user_id' INTEGER ASC DEFAULT '',
  'user_name' varchar(64) NOT NULL DEFAULT '',
  'user_name_clean' varchar(64) NOT NULL DEFAULT '',
  'user_password' varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY ('user_id'),
  UNIQUE ('user_name' ,'user_name_clean')
);

CREATE TABLE  'groups' (
'group_id' INTEGER ASC DEFAULT '',
'group_name' VARCHAR( 64 ) NOT NULL DEFAULT '',
PRIMARY KEY ('group_id'),
UNIQUE ('group_name')
);

CREATE TABLE  'user_group' (
'user_group_id' INTEGER ASC DEFAULT '',
'user_id' VARCHAR( 64 ) NOT NULL DEFAULT '',
'group_id' VARCHAR( 64 ) NOT NULL DEFAULT '',
PRIMARY KEY ('user_group_id')
)
";
	} elseif ( 'mysql' === $CONFIG_DBTYPE ) {
		$dbTableLocks = $CONFIG_DBTABLEPREFIX . 'locks';
		$dbTableLog = $CONFIG_DBTABLEPREFIX . 'log';
		$dbTableProperties = $CONFIG_DBTABLEPREFIX . 'properties';
		$dbTableUsers = $CONFIG_DBTABLEPREFIX . 'users';
		$dbTableGroups = $CONFIG_DBTABLEPREFIX . 'groups';
		$dbTableUserGroup = $CONFIG_DBTABLEPREFIX . 'user_group';

		$query = "CREATE TABLE IF NOT EXISTS `$dbTableLocks` (
  `token` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(200) NOT NULL DEFAULT '',
  `created` int(11) NOT NULL DEFAULT '0',
  `modified` int(11) NOT NULL DEFAULT '0',
  `expires` int(11) NOT NULL DEFAULT '0',
  `owner` varchar(200) DEFAULT NULL,
  `recursive` int(11) DEFAULT '0',
  `writelock` int(11) DEFAULT '0',
  `exclusivelock` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`token`),
  UNIQUE KEY `token` (`token`),
  KEY `path` (`path`),
  KEY `path_2` (`path`),
  KEY `path_3` (`path`,`token`),
  KEY `expires` (`expires`)
);

CREATE TABLE IF NOT EXISTS `$dbTableLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `user` varchar(250) NOT NULL,
  `type` int(11) NOT NULL,
  `message` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
);


CREATE TABLE IF NOT EXISTS `$dbTableProperties` (
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(120) NOT NULL DEFAULT '',
  `ns` varchar(120) NOT NULL DEFAULT 'DAV:',
  `value` text,
  PRIMARY KEY (`path`,`name`,`ns`),
  KEY `path` (`path`)
);

CREATE TABLE IF NOT EXISTS  `$dbTableUsers` (
`user_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_name` VARCHAR( 64 ) NOT NULL ,
`user_name_clean` VARCHAR( 64 ) NOT NULL ,
`user_password` VARCHAR( 340) NOT NULL ,
UNIQUE (
`user_name` ,
`user_name_clean`
)
);

CREATE TABLE IF NOT EXISTS  `$dbTableGroups` (
`group_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`group_name` VARCHAR( 64 ) NOT NULL ,
UNIQUE (
`group_name`
)
);

CREATE TABLE IF NOT EXISTS  `$dbTableUserGroup` (
`user_group_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` VARCHAR( 64 ) NOT NULL ,
`group_id` VARCHAR( 64 ) NOT NULL
)
";
	}
      OC_DB::multiquery($query);
   }
   
   /**
   * Create the database and user
   * @param string adminUser
   * @param string adminPwd
   *
   */
  private static function createdatabase($adminUser,$adminPwd){
      global $CONFIG_DBHOST;
      global $CONFIG_DBNAME;
      global $CONFIG_DBUSER;
      global $CONFIG_DBPWD;
      //we cant user OC_BD functions here because we need to connect as the administrative user.
      $connection = @new mysqli($CONFIG_DBHOST, $adminUser, $adminPwd);
      if (mysqli_connect_errno()) {
         @ob_end_clean();
         echo('<html><head></head><body bgcolor="#F0F0F0"><br /><br /><center><b>can not connect to database as administrative user.</center></body></html>');
         exit();
      }
      $query="CREATE USER '{$_POST['dbuser']}' IDENTIFIED BY  '{$_POST['dbpassword']}';

CREATE DATABASE IF NOT EXISTS  `{$_POST['dbname']}` ;

GRANT ALL PRIVILEGES ON  `{$_POST['dbname']}` . * TO  '{$_POST['dbuser']}';";
      $result = @$connection->multi_query($query);
      if (!$result) {
         $entry='DB Error: "'.$connection->error.'"<br />';
         $entry.='Offending command was: '.$query.'<br />';
         echo($entry);
      }
      $connection->close();
   }
}
?>



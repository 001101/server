<?php

/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/


//require_once('../../config/config.php');
require_once('../lib/base.php');
require( 'template.php' );
require( 'preferences.php' );

if( !OC_USER::isLoggedIn()){
    header( 'Location: '.OC_HELPER::linkTo( 'index.php' ));
    exit();
}

//load the script
OC_UTIL::addScript( "log", "log" );

$allActions=array('login','logout','read','write','create','delete');

//check for a submited config
if(isset($_POST['size'])){
	$selectedActions=array();
	foreach($allActions as $action){
		if(isset($_POST[$action]) and $_POST[$action]=='on'){
			$selectedActions[]=$action;
		}
	}
	OC_PREFERENCES::setValue($_SESSION['user_id'],'log','actions',implode(',',$selectedActions));
	OC_PREFERENCES::setValue($_SESSION['user_id'],'log','pagesize',$_POST['size']);
}

OC_APP::setActiveNavigationEntry( 'log' );
$logs=OC_LOG::get();

$selectedActions=explode(',',OC_PREFERENCES::getValue($_SESSION['user_id'],'log','actions',implode(',',$allActions)));
$logs=OC_LOG::filterAction($logs,$selectedActions);

$pageSize=OC_PREFERENCES::getValue($_SESSION['user_id'],'log','pagesize',20);
$pageCount=ceil(count($logs)/$pageSize);
$page=isset($_GET['page'])?$_GET['page']:0;
if($page>=$pageCount){
	$page=$pageCount-1;
}

$logs=array_slice($logs,$page*$pageSize,$pageSize);

foreach( $logs as &$i ){
	$i['date'] =$i['moment'];
}

$url=OC_HELPER::linkTo( 'log', 'index.php' ).'?page=';
$pager=OC_UTIL::getPageNavi($pageCount,$page,$url);
if($pager){
	$pagerHTML=$pager->fetchPage();
}else{
	$pagerHTML='';
}

$showActions=array();
foreach($allActions as $action){
	if(array_search($action,$selectedActions)!==false){
		$showActions[$action]='checked="checked"';
	}else{
		$showActions[$action]='';
	}
}

$tmpl = new OC_TEMPLATE( 'log', 'index', 'admin' );
$tmpl->assign( 'logs', $logs );
$tmpl->assign( 'pager', $pagerHTML );
$tmpl->assign( 'size', $pageSize );
$tmpl->assign( 'showActions', $showActions );
$tmpl->printPage();

?>

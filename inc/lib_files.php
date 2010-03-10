<?php

/**
* ownCloud
*
* @author Frank Karlitschek 
* @copyright 2010 Frank Karlitschek karlitschek@kde.org 
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/



/**
 * Class for fileserver access
 *
 */
class OC_FILES {

  /**
   * show a web GUI filebrowser
   *
   * @param basedir $basedir
   * @param dir $dir
   */
  public static function showbrowser($basedir,$dir){
    global $CONFIG_DATEFORMAT;

    $directory=$basedir.'/'.$dir;

    // exit if try to access files outside our directory
    if(strstr($dir,'..')<>false) exit();
    $directory=realpath($directory);

    $dirs=explode('/',$dir);

    // breadcrumb
    if(count($dirs)>1) {
      echo('<center><table cellpadding="2" cellspacing="0" border="0"><tr>');
      echo('<td class="nametext"><a href="/">home</a></td>');
      $currentdir='';
      foreach($dirs as $d) {
        $currentdir.='/'.$d.'';
        if($d<>'') echo('<td class="nametext"><a href="/?dir='.$currentdir.'"><img src="/img/arrow.png" border="0" />&nbsp;'.$d.'</a></td>');
      }
      echo('</tr></table></center>');
    }

    // files and directories
    echo('<center><table cellpadding="6" cellspacing="0" border="0" class="browser">');
    if (is_dir($directory)) {
      if ($dh = opendir($directory)) {
        $filesfound=false;
        while (($file = readdir($dh)) !== false) {
          if($file<>'.' and $file<>'..'){
            $filesfound=true;
            $stat=stat($directory.'/'.$file);
            $filetype=filetype($directory .'/'. $file);
            echo('<tr class="browserline">');
            OC_UTIL::showicon($filetype);

            if($filetype=='dir') echo('<td class="nametext"><a href="/?dir='.$dir.'/'.$file.'">'.$file.'</a></td>');
            if($filetype<>'dir') echo('<td class="nametext"><a href="/?dir='.$dir.'&file='.$file.'">'.$file.'</a></td>');
            if($filetype<>'dir') echo('<td class="sizetext">'.$stat['size'].' byte</td>'); else echo('<td></td>');
            echo('<td class="sizetext">'.date($CONFIG_DATEFORMAT,$stat['mtime']).'</td>');
            echo('</tr>');
          }
        }
      closedir($dh);
      }
    }
    echo('</table>');
    if(!$filesfound) echo('<br />no files here');
    echo('</center>');
  }



  /**
   * return the cntent of a file
   *
   * @param dir  $dir
   * @param file $file
   */
  public static function get($dir,$file){
    if(isset($_SESSION['username']) and $_SESSION['username']<>'') {
      global $CONFIG_DATADIRECTORY;
      $filename=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;

      // exit if try to access files outside our directory
      if(strstr($filename,'..')<>false) exit();

      OC_LOG::event($_SESSION['username'],3,$dir.'/'.$file);

      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename='.basename($file));
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($filename));
      readfile($filename);
    }
    exit;
  }


}



?>

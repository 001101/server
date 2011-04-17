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
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

OC_APP::setActiveNavigationEntry( "log" );
$logs=OC_LOG::get( $dir );

foreach( $logs as &$i ){
    $i["date"] = date( $CONFIG_DATEFORMAT, $i['timestamp'] );
    $i["action"] = OC_LOG::$TYPE[$i['type']];
}

$tmpl = new OC_TEMPLATE( "log", "index", "admin" );
$tmpl->assign( "logs", $logs );
$tmpl->printPage();

?>

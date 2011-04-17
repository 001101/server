<?php

OC_APP::register( array( "id" => "settings", "name" => "Settings" ));
OC_APP::addSettingsPage( array( "id" => "settings", "order" => -1000, "href" => OC_HELPER::linkTo( "settings", "index.php" ), "name" => "Information", "icon" => OC_HELPER::imagePath( "settings", "information.png" )));

?>

<?php
/*
 * Template for error page
 */
?>
<div id="login">
	<img src="<?php echo image_path("", "owncloud-logo-medium-white.png"); ?>" alt="ownCloud" />
	<ul>
		<?php foreach($_["errors"] as $error):?>
			<li class='error'>
				<?php echo $error['error'] ?><br/>
				<p class='hint'><?php echo $error['hint'] ?></p>
			</li>
		<?php endforeach ?>
	</ul>
</div>


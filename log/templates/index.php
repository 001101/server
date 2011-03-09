<?php
/*
 * Template for admin pages
 */
?>
<h1>Logs</h1>

<div class="controls">
	<form id="logs_options">
		<p>
			<span>Filter :</span>

			<input type="checkbox" checked="checked" name="all" id="all" /> <label for="all">All</label>
			<input type="checkbox" checked="checked" name="logins" id="logins" /> <label for="logins">Logins</label>
			<input type="checkbox" checked="checked" name="logouts" id="logouts" /> <label for="logouts">Logouts</label>
			<input type="checkbox" checked="checked" name="downloads" id="downloads" /> <label for="downloads">Downloads</label>
			<input type="checkbox" checked="checked" name="uploads" id="uploads" /> <label for="uploads">Uploads</label>

			<input type="checkbox" checked="checked" name="creations" id="creations" /> <label for="creations">Creations</label>
			<input type="checkbox" checked="checked" name="deletions" id="deletions" /> <label for="deletions">Deletions</label>
		</p>
		<p>
			<span>Show :</span>
			<input type="text" maxlength="3" size="3" value="10" />&nbsp;entries per page.
			<input type="submit" value="Save" />

		</p>
	</form>
</div>

<table cellspacing="0">
	<thead>
		<tr>
			<th>What</th>
			<th>When</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["logs"] as $entry): ?>
			<tr>
				<td class="login"><em><?php echo $entry["user"]; ?></em> <?php echo $entry["message"]; ?></td>
				<td class="date"><?php echo $entry["date"]; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="controls">
    <p class="center"><a href="" title="Previous page">&larr;</a>&nbsp;3/5&nbsp;<a href="" title="Next page">&rarr;</a></p>
</div>

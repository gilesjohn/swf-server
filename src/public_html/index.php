<?php 
require_once(__DIR__ . '/../lib/account.php');
Account::blockNonAuthenticated();
?>

<!DOCTYPE html>
<html>
	<head></head>
	<body>
		<!--
			/playback/play/ - play track - GET
			/playback/pause/ - pause track - GET
			/playback/next/ - next track - GET
			/playback/prev/ - previous track - GET
			/playback/playtrack/ - play a specified uri with optional timestamp - POST {uri, (timestamp)}
			/playback/state/ - find the playback state - GET
		-->
		
		
		<h1>You are logged in as: <?php echo $_COOKIE['username']; ?></h1>
		<form method="get" action="/playback/play/">
			<input type="submit" value="play">
		</form>
		
		<hr>
		
		<form method="get" action="/playback/pause/">
			<input type="submit" value="pause">
		</form>
		
		<hr>
		
		<form method="get" action="/playback/next/">
			<input type="submit" value="next">
		</form>
		
		<hr>
		
		<form method="get" action="/playback/prev/">
			<input type="submit" value="prev">
		</form>
		
		<hr>
		
		<form method="post" action="/playback/playtrack/">
			Uri: <input type="text" name="uri"><br>
			Timestamp: <input type="text" name="timestamp" value="5000"><br>
			<input type="submit" value="play track">
		</form>
		
		<hr>
		
		<form method="get" action="/playback/state/">
			<input type="submit" value="get state">
		</form>
		<a href="/account/logout/">log out</a>
	</body>
</html>
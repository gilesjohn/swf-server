<?php
require_once(__DIR__ . '/../lib/conf.php');
require_once(__DIR__ . '/../lib/db.php');
require_once(__DIR__ . '/../lib/account.php');

if (isset($_POST['username']) && isset($_POST['password'])) {
	Account::register($_POST['username'], $_POST['password']);
	exit();
}

?>

<!DOCTYPE html>
<html>
	<head></head>
	<body>
		<form method="post">
			Username: <input type="text" name="username"><br>
			Password: <input type="password" name="password"><br>
			<input type="submit">
		</form>
	</body>
</html>
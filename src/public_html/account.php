<?php  declare(strict_types=1);
require_once(__DIR__ . '/../lib/account.php');


if (isset($_GET['action'])) {
	$action = $_GET['action'];
	switch($action) {
		case 'login':
			if (!isset($_POST['username'])) {
				header('HTTP/1.0 400 Bad Request');
				echo "Username not provided";
				exit();
			}
			if (!isset($_POST['password'])) {
				header('HTTP/1.0 400 Bad Request');
				echo "Password not provided";
				exit();
			}
			Account::logIn($_POST['username'], $_POST['password']);
			break;
		case 'logout':
			Account::logOut();
			break;
		case 'authorise':
			$username = Account::blockNonAuthenticated();
			if (!isset($_POST['redirect'])) {
				header('HTTP/1.0 400 Bad Request');
				echo "Redirect URL not provided";
				exit();
			}
			$state = substr(bin2hex(random_bytes(CALLBACK_STATE_LENGTH)),0,CALLBACK_STATE_LENGTH);
			$conn = getMysqliConnection();
			query($conn, 'INSERT INTO spotify_callback (state, url, username) VALUES (?,?,?)', 'sss', array($state, $_POST['redirect'], $username));
			$conn->close();
			echo AUTH_LINK . $state;
			break;
		default:
			header('HTTP/1.0 404 Not Found');
			echo "Unsupported action: " . $action;
	}
	exit();
}
echo "No action set";
throw new Exception("No action set");
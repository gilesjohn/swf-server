<?php  declare(strict_types=1);
require_once(__DIR__ . '/../lib/playback.php');
require_once(__DIR__ . '/../lib/account.php');



$username = Account::blockNonAuthenticated();

if (isset($_GET['action'])) {
	$action = $_GET['action'];
	switch($action) {
		case 'play':
			Playback::play($username);
			header('HTTP/1.0 204 No Content');
			break;
		case 'pause':
			Playback::pause($username);
			header('HTTP/1.0 204 No Content');
			break;
		case 'next':
			Playback::nextTrack($username);
			header('HTTP/1.0 204 No Content');
			break;
		case 'prev':
			Playback::prevTrack($username);
			header('HTTP/1.0 204 No Content');
			break;
		case 'playtrack':
			if (isset($_POST['timestamp'])) {
				$timestamp = intval($_POST['timestamp']);
				if ($timestamp === 0 && $_POST['timestamp'] !== '0') {
					echo "Invalid timestamp";
					throw new Exception("Invalid timestamp");
				}
			}
			Playback::playTrack($username, $_POST['uri'], $_POST['timestamp']);
			header('HTTP/1.0 204 No Content');
			break;
		case 'state':
        	header('Content-Type: application/json');
			echo json_encode(Playback::state($username));
			break;
		default:
			header('HTTP/1.0 404 Not Found');
			echo "Unsupported action: " . $action;
	}
	exit();
}
header('HTTP/1.0 404 Not Found');
echo "No action set";
exit();
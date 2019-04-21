<?php  declare(strict_types=1);
require_once(__DIR__ . '/../lib/db.php');
require_once(__DIR__ . '/../lib/account.php');

$username = Account::blockNonAuthenticated();

if (isset($_GET['id'])) {
	if (isset($_GET['action'])) {
		if ($_GET['action'] === 'add') {
			$conn = getMysqliConnection();
			try {
				$added = query($conn, 'INSERT INTO friends (leader, listener, listening) SELECT username, ?, ? FROM users WHERE username = ?', 'sis', array($username, false, $_GET['id']));
				if ($added === 0) {
					header('HTTP/1.0 404 Not Found');
					echo 'No such user.';
				}
			} catch (mysqli_sql_exception $e) {
				//header('HTTP/1.0 409 Conflict');
				if (strpos(strtolower($e->message),'duplicate entry') !== FALSE) {
					echo 'Friend already added.';
				} else {
					echo 'Unknown mysql error: ' . $e->message;
				}
			}
			$conn->close();
		} else if ($_GET['action'] === 'listen') {
			$conn = getMysqliConnection();
			query($conn, 'UPDATE friends SET listening = false WHERE listener = ?', 's', array($username));
			$added = query($conn, 'UPDATE friends SET listening = true WHERE leader = ? AND listener = ?', 'ss', array($_GET['id'], $username));
			if ($added === 0) {
				header('HTTP/1.0 404 Not Found');
				echo 'No such friend.';
			}
			$conn->close();
		} else if ($_GET['action'] === 'leave') {
			$conn = getMysqliConnection();
			$left = query($conn, 'UPDATE friends SET listening = false WHERE leader = ? AND listener = ? AND listening = true', 'ss', array($_GET['id'], $username));
			if ($left === 0) {
				header('HTTP/1.0 404 Not Found');
				echo 'You weren\'t listening to them.';
			}
			$conn->close();
		} else if ($_GET['action'] === 'remove') {
			$conn = getMysqliConnection();
			$removed = query($conn, 'DELETE FROM friends WHERE leader = ? AND listener = ?', 'ss', array($_GET['id'], $username));
			if ($removed === 0) {
				header('HTTP/1.0 404 Not Found');
				echo 'No such friend.';
			}
			$conn->close();
		} else {
			header('HTTP/1.0 404 Not Found');
			echo 'Unsupported action';
			exit();
		}
	} else {
		header('HTTP/1.0 400 Bad Request');
		echo 'Action not provided';
		exit();
	}
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$state = array("friends"=>array());
	$conn = getMysqliConnection();
	$results = query($conn, 'SELECT leader, listening FROM friends WHERE listener = ?', 's', array($username));
	$conn->close();
	if ($results !== NULL) {
		foreach ($results as $row) {
			array_push($state['friends'], array('username'=>$row->leader, 'listening'=>$row->listening));
		}
	}
	header('Content-Type: application/json');
	echo json_encode($state);
}

<?php
	include_once(__DIR__ . '/../lib/conf.php');
	include_once(__DIR__ . '/../lib/db.php');
	include_once(__DIR__ . '/../lib/account.php');
	// User is sent here after spotify authorisation, can get code to add to account manually
	if (isset($_GET['state']) && isset($_GET['code'])) {
		$conn = getMysqliConnection();
		
		$results = query($conn, 'SELECT username, url FROM spotify_callback WHERE state = ?', 's', array($_GET['state']));
		query($conn, 'DELETE FROM spotify_callback WHERE state=?', 's', array($_GET['state']));
		if ($results === NULL || count($results) !== 1) {
			$conn->close();
			$error = 'Error looking up callback state';
			echo $error;
			throw new Exception($error);
		}
		
		Account::AuthoriseSpotify($conn, $results[0]->username, $_GET['code']);

		$conn->close();
		header("Location: " . $results[0]->url);
		exit();
	}
	$error = 'Unable to authorise with spotify';
	echo $error;
	throw new Exception($error);

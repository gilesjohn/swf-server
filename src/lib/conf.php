<?php
	define('SESSION_KEY_LENGTH', 64);
	define('SESSION_LENGTH', 86400); // 1 day
	define('CALLBACK_STATE_LENGTH', 64);

	define('DB_HOST', '127.0.0.1');
	define('DB_USERNAME', '***CHANGE THIS***');
	define('DB_PASSWORD', '***CHANGE THIS***');
	define('DB_NAME', '***CHANGE THIS***');
	define('DB_PORT', 3306);

	define('CALLBACK_URL', 'https://***CHANGE THIS***/callback.php');

	define('CLIENT_ID', '***CHANGE THIS***');
	define('CLIENT_SECRET', '***CHANGE THIS***');

	define('SWF_AUTH_MSG', 'Not authenticated with swf api');
	define('SPOTIFY_AUTH_MSG', 'Not authenticated with Spotify');

	define('PROGRESS_DIFFERENCE_THRESHOLD', 3000);

	define('AUTH_LINK', "https://accounts.spotify.com/authorize?client_id=" . CLIENT_ID . "&response_type=code&redirect_uri=" . urlencode(CALLBACK_URL) . "&scope=user-modify-playback-state%20user-read-playback-state&state=");
<?php  declare(strict_types=1);
require_once(__DIR__ . '/conf.php');
require_once(__DIR__ . '/db.php');
require_once(__DIR__ . '/curlwrapper.php');


/* Store users spotify code in db
*/
function storeCode($conn, $username, $code) {
	query($conn, 'UPDATE users SET code = ? WHERE username = ?', 'ss', array($code, $username));
}
/* Get users spotify code from db
*/
function fetchCode($conn, $username) {
	$result = query($conn, 'SELECT code FROM users WHERE username = ?', 's', array($username));
	if ($result != NULL) {
		return $result[0]->code;
	} else {
		return NULL;
	}
}

function storeRefresh($conn, $username, $refreshCode) {
	query($conn, 'UPDATE users SET refresh = ? WHERE username = ?', 'ss', array($refreshCode, $username));
}
function fetchRefresh($conn, $username) {
	$result = query($conn, 'SELECT refresh FROM users WHERE username = ?', 's', array($username));
	if ($result != NULL) {
		return $result[0]->refresh;
	} else {
		return NULL;
	}
}

function storeAccess($conn, $username, $accessCode) {
	query($conn, 'UPDATE users SET access = ? WHERE username = ?', 'ss', array($accessCode, $username));
}
function fetchAccess($conn, $username) {
	$result = query($conn, 'SELECT access FROM users WHERE username = ?', 's', array($username));
	if ($result != NULL) {
		return $result[0]->access;
	} else {
		return NULL;
	}
}

function storeAccessExpire($conn, $username, $accessExpire) {
	query($conn, 'UPDATE users SET access_expire = ? WHERE username = ?', 'ss', array($accessExpire, $username));
}
function fetchAccessExpire($conn, $username) {
	$result = query($conn, 'SELECT access_expire FROM users WHERE username = ?', 's', array($username));
	if ($result != NULL) {
		return $result[0]->access_expire;
	} else {
		return NULL;
	}
}

function getAccess($conn, $username) {
	$code = fetchCode($conn, $username);
	if ($code === NULL) {
		header('HTTP/1.0 401 Unauthorized');
		echo 'Not authenticated with Spotify';
		exit();
	}
	$access = fetchAccess($conn, $username);
	$accessExpire = fetchAccessExpire($conn, $username);
	$refresh = fetchRefresh($conn, $username);
	if ($access !== NULL && $accessExpire !== NULL) {
		if ($accessExpire < time()) {
			if ($refresh === NULL) {
				$error = "Refresh token doesnt exist";
				echo $error;
				throw new Exception($error);
			}
			return refreshAccess($conn, $username);
		} else {
			return $access;
		}
	} else if ($access === NULL || $accessExpire === NULL) {
		$headers = array();
		$data = array(
			"client_id" => CLIENT_ID,
			"client_secret" => CLIENT_SECRET,
			"code" => $code,
			"grant_type" => "authorization_code",
			"redirect_uri" => CALLBACK_URL
		);
		$response = httpPost('https://accounts.spotify.com/api/token', $headers, $data);
		if ($response->code != 200) {
			$error = "Unable to get access token from Spotify. Error: " . $response->body;
			echo $error;
			throw new Exception($error);
		}
		$response_data = json_decode($response->body, true);
		if (array_key_exists("access_token", $response_data) && array_key_exists("expires_in", $response_data)) {
			$access = $response_data['access_token'];
			storeAccess($conn, $username, $access);
			storeAccessExpire($conn, $username, time() + $response_data["expires_in"]);
			if (array_key_exists("refresh_token", $response_data)) {
				storeRefresh($conn, $username, $response_data['refresh_token']);
			}
		} else {
			var_dump($response);
			$error = "Couldn't get an access token: " . $response_data['error'];
			if (array_key_exists("error_description", $response_data)) {
				$error .= '<br>' . $response_data['error_description'];
			}
			echo $error;
			throw new Exception($error);
		}
	}
	return $access;
}
function refreshAccess($conn, $username) {
	$headers = array("Authorization" => "Basic " . base64_encode(CLIENT_ID . ':' . CLIENT_SECRET));
	$data = array(
		"grant_type" => "refresh_token",
		"refresh_token" => fetchRefresh($conn, $username)
	);
	$response = httpPost('https://accounts.spotify.com/api/token', $headers, $data);
	if ($response->code != 200) {
			$error = "Unable to get access token from Spotify. Error: " . $response->body;
			echo $error;
			throw new Exception($error);
	}
	$response_data = json_decode($response->body, true);
	if (array_key_exists("access_token", $response_data) && array_key_exists("expires_in", $response_data)) {
		$access = $response_data['access_token'];
		storeAccess($conn, $username, $access);
		storeAccessExpire($conn, $username, time() + $response_data["expires_in"]);
		if (array_key_exists("refresh_token", $response_data)) {
			storeRefresh($conn, $username, $response_data['refresh_token']);
		}
		return $access;
	} else {
		var_dump($response);
		$error = "Couldn't get an access token: " . $response_data['error'];
		if (array_key_exists("error_description", $response_data)) {
			$error .= '<br>' . $response_data['error_description'];
		}
		echo $error;
		throw new Exception($error);
	}
}

function formatPlaybackData($playbackDataJson) {
	$playbackState = array();
	$playbackState['isPlaying'] = $playbackDataJson['is_playing'];
	$playbackState['albumCoverURL'] = $playbackDataJson['item']['album']['images'][0]['url'];
	$playbackState['track'] = $playbackDataJson['item']['name'];
	$artists = array();
	foreach ($playbackDataJson['item']['artists'] as $artist) {
		array_push($artists, $artist['name']);
	}
	$playbackState['artist'] = implode(', ', $artists);
	$playbackState['album'] = $playbackDataJson['item']['album']['name'];
	$playbackState['progress'] = $playbackDataJson['progress_ms'];
	$playbackState['length'] = $playbackDataJson['item']['duration_ms'];

	return $playbackState;
}

function getCurrentPlaybackData($conn, $username) {
	$accessCode = getAccess($conn, $username);

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$response = httpGet('https://api.spotify.com/v1/me/player', $headers);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
	$response_data = json_decode($response->body, true);
	/*
	{
		"device":{
			"id":"9bd3f21d103e112ff67b64c1c6b1163d32c78b27",
			"is_active":true,
			"is_private_session":false,
			"is_restricted":false,
			"name":"GJ-DESKTOP",
			"type":"Computer",
			"volume_percent":60
		},
		"shuffle_state":false,
		"repeat_state":"off",
		"timestamp":1554339817090,
		"context":{
			"external_urls":{
				"spotify":"https://open.spotify.com/playlist/2e8anG3x7mFDsfspLkZLaz"
			},
			"href":"https://api.spotify.com/v1/playlists/2e8anG3x7mFDsfspLkZLaz",
			"type":"playlist",
			"uri":"spotify:user:gilesjohn2:playlist:2e8anG3x7mFDsfspLkZLaz"
		},
		"progress_ms":21371,
		"item":{
			"album":{
				"album_type":"single",
				"artists":[
					{
						"external_urls":{
							"spotify":"https://open.spotify.com/artist/5OjwToGCaI7xwzN5l27VIN"
						},
						"href":"https://api.spotify.com/v1/artists/5OjwToGCaI7xwzN5l27VIN",
						"id":"5OjwToGCaI7xwzN5l27VIN",
						"name":"PHFAT",
						"type":"artist",
						"uri":"spotify:artist:5OjwToGCaI7xwzN5l27VIN"
					}
				],
				"available_markets":[
					"AD","AE","AR","AT","AU","BE","BG","BH","BO",
					...
					"ZA"
				],
				"external_urls":{
					"spotify":"https://open.spotify.com/album/7bF7VMtntwIB1FoUZ1MijK"
				},
				"href":"https://api.spotify.com/v1/albums/7bF7VMtntwIB1FoUZ1MijK",
				"id":"7bF7VMtntwIB1FoUZ1MijK",
				"images":[
					{
						"height":640,
						"url":"https://i.scdn.co/image/84ff8b85ce427a158b9e2547dd55c3cd2355d376",
						"width":640
					},
					{
						"height":300,
						"url":"https://i.scdn.co/image/971ee1fdb3e1e11d5ea18caf9461b1beab0ec983",
						"width":300
					},
					{
						"height":64,
						"url":"https://i.scdn.co/image/e05ae358fb8c1a31c0b161a1ddf6b28e9c2b186a",
						"width":64
					}
				],
				"name":"Suedes",
				"release_date":"2018-08-31",
				"release_date_precision":"day",
				"total_tracks":1,
				"type":"album",
				"uri":"spotify:album:7bF7VMtntwIB1FoUZ1MijK"
			},
			"artists":[
				{
					"external_urls":{
						"spotify":"https://open.spotify.com/artist/5OjwToGCaI7xwzN5l27VIN"
					},
					"href":"https://api.spotify.com/v1/artists/5OjwToGCaI7xwzN5l27VIN",
					"id":"5OjwToGCaI7xwzN5l27VIN",
					"name":"PHFAT",
					"type":"artist",
					"uri":"spotify:artist:5OjwToGCaI7xwzN5l27VIN"
				}
			],
			"available_markets":[
				"AD",
				"AE",
				...
				"VN",
				"ZA"
			],
			"disc_number":1,
			"duration_ms":220552,
			"explicit":true,
			"external_ids":{
				"isrc":"GBGLW1800205"
			},
			"external_urls":{
				"spotify":"https://open.spotify.com/track/0wH71JGtyZSclyClnY4X3H"
			},
			"href":"https://api.spotify.com/v1/tracks/0wH71JGtyZSclyClnY4X3H",
			"id":"0wH71JGtyZSclyClnY4X3H",
			"is_local":false,
			"name":"Suedes",
			"popularity":40,
			"preview_url":"https://p.scdn.co/mp3-preview/87546a286cc9292feb9f17591d4833fd596fbeba?cid=99f9c4dfbcc94befa6e8b9897f265cdb",
			"track_number":1,
			"type":"track",
			"uri":"spotify:track:0wH71JGtyZSclyClnY4X3H"
		},
		"currently_playing_type":"track",
		"actions":{
			"disallows":{
				"pausing":true,
				"skipping_prev":true
			}
		},
		"is_playing":false
	}

	*/
	return $response_data;
}

function play($conn, $username) {
	$accessCode = getAccess($conn, $username);

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$response = httpPut('https://api.spotify.com/v1/me/player/play', $headers, NULL);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
}
function pause($conn, $username) {
	$accessCode = getAccess($conn, $username);

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$data = array();
	$response = httpPut('https://api.spotify.com/v1/me/player/pause', $headers, $data);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
}
function nextTrack($conn, $username) {
	$accessCode = getAccess($conn, $username);

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$data = array();
	$response = httpPost('https://api.spotify.com/v1/me/player/next', $headers, $data);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
}
function prevTrack($conn, $username) {
	$accessCode = getAccess($conn, $username);

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$data = array();
	$response = httpPost('https://api.spotify.com/v1/me/player/previous', $headers, $data);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
}

function playTrack($conn, $username, $uri, $progress) {
	$accessCode = getAccess($conn, $username);
	if ($progress === NULL) {
		$progress = 0;
	}

	$headers = array("Authorization" => "Bearer " . $accessCode);
	$data = array('uris' => array($uri), 'position_ms' => $progress);
	$response = httpPut('https://api.spotify.com/v1/me/player/play', $headers, $data);
	if ($response->code == 401) {
		header('HTTP/1.0 401 Unauthorized');
		echo SPOTIFY_AUTH_MSG;
		exit();
	}
}

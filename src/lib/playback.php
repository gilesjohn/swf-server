<?php declare(strict_types=1);
require_once(__DIR__ . '/spotify.php');

class Playback {
	public static function play($username) {
		$conn = getMysqliConnection();
		play($conn, $username);
		$conn->close();
	}
	
	public static function pause($username) {
		$conn = getMysqliConnection();
		pause($conn, $username);
		$conn->close();
	}
	
	public static function nextTrack($username) {
		$conn = getMysqliConnection();
		nextTrack($conn, $username);
		$conn->close();
	}
	
	public static function prevTrack($username) {
		$conn = getMysqliConnection();
		prevTrack($conn, $username);
		$conn->close();
	}
	
	public static function playTrack($username, $uri, $progress) {
		$conn = getMysqliConnection();
		playTrack($conn, $username, $uri, $progress);
		$conn->close();
	}
	
	public static function state($username) {
		$conn = getMysqliConnection();
		$playbackData = getCurrentPlaybackData($conn, $username);
		$conn->close();
		return formatPlaybackData($playbackData);
	}
}
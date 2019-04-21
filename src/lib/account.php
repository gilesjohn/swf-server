<?php  declare(strict_types=1);
require_once(__DIR__ . '/conf.php');
require_once(__DIR__ . '/db.php');

class Account {
	public static function logIn(string $username, string $password) {
		$conn = getMysqliConnection();
		$hash = query($conn, 'SELECT password FROM users WHERE username = ?', 's', array($username));
		if ($hash != NULL && count($hash) === 1) {
			if (password_verify($password, $hash[0]->password)) {
				echo Account::createSession($conn, $username);
				$conn->close();
				return;
			}
		}
		$conn->close();
		header('HTTP/1.0 401 Unauthorized');
		echo "Invalid credentials";
		exit();
	}
	public static function logOut() {
		$headers = getallheaders();
		if (isset($headers['Authentication'])) {
			$conn = getMysqliConnection();

			Account::endSession($conn, $headers['Authentication']);
			$conn->close();
			echo "Logged out";
		}
	}
	public static function register(string $username, string $password) {
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$callbackCode = substr(bin2hex(random_bytes(CALLBACK_STATE_LENGTH)),0,CALLBACK_STATE_LENGTH);
		$conn = getMysqliConnection();
		query($conn, 'INSERT INTO users (username, password, callback_code) VALUES (?,?,?)', 'sss', array($username, $hash, $callbackCode));
		query($conn, 'INSERT INTO sessions (session_id, username, session_start, session_end) VALUES (?,?,?,?)', 'isii', array(Account::generateSessionID(), $username, 0, 0));
		$conn->close();
		echo "Registered";
	}
	public static function AuthoriseSpotify($conn, $user, $code) {
		query($conn, 'UPDATE users SET code = ?, refresh = NULL, access = NULL, access_expire = NULL WHERE username = ?', 'ss', array($code, $user));
	}
	// Check if logged in given mysqli and session, return username on login or false if not
	protected static function isLoggedIn($conn, string $sessionID) {
		$dbSession = query($conn, 'SELECT username, session_end FROM sessions WHERE session_id = ?', 's', array($sessionID));
		if ($dbSession != NULL && count($dbSession) === 1) {
			if (time() < $dbSession[0]->session_end) {
				return $dbSession[0]->username;
			}
		}
		return FALSE;
	}
	// Checks logged in and returns username
	public static function blockNonAuthenticated() {
		$headers = getallheaders();
		if (isset($headers['Authentication'])) {
			$conn = getMysqliConnection();
		
			$username = Account::isLoggedIn($conn, $headers['Authentication']);
			$conn->close();
			if ($username !== FALSE) {
				return $username;
			} else {
				header('HTTP/1.0 401 Unauthorized');
				echo "Invalid authentication header";
				exit();
			}
		} else {
			header('HTTP/1.0 401 Unauthorized');
			echo "No authentication header set";
			exit();
		}
		
		
	}
	protected static function generateSessionID() {
		return substr(bin2hex(random_bytes(SESSION_KEY_LENGTH)),0,SESSION_KEY_LENGTH);
	}
	
	protected static function createSession($conn, string $username) {
		$sessionID = Account::generateSessionID();
		$sessionStart = time();
		$sessionEnd = $sessionStart + SESSION_LENGTH;
		query($conn, 'UPDATE sessions SET session_id = ?, session_start = ?, session_end = ? WHERE username = ?', 'siis', array($sessionID, $sessionStart, $sessionEnd, $username));
		return $sessionID;
	}
	
	protected static function endSession($conn, string $sessionID) {
		$sessionEnd = 0;
		query($conn, 'UPDATE sessions SET session_end = ? WHERE session_id = ?', 'is', array($sessionEnd, $sessionID));
	}
}
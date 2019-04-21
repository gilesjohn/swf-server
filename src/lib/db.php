<?php  declare(strict_types=1);
require_once(__DIR__ . '/conf.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get a mysql connection to work with
function getMysqliConnection():mysqli {
	$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
	if ($conn->connect_error) {
		throw new Exception("Connection failed: " . $conn->connect_error);
	}
	return $conn;
}

/* Execute mysql query and return result
Params:
	$conn - mysqli object with connection to db
	$statement - string mysql prepared statement
	$types - string format for binding prepared statement
	$parameters - array data to bind to prepared statement
Return - array of row objects resulting from query or null if nothing results from query
*/
function query($conn, string $statement, string $types, array $parameters) {
	$stmt = $conn->prepare($statement);
	if (count($parameters) > 0 || $types !== '') {
		$stmt->bind_param($types, ...$parameters);
	}
	if ($stmt->execute() === FALSE) {
		throw new Exception("Unable to execute statement: " . $statement . "\nUsing parameters: " . implode(", ", $parameters) . "\nError: " . $stmt->error);
	}
	if ((strpos(strtolower($statement), 'update') !== false || strpos(strtolower($statement), 'insert') !== false || strpos(strtolower($statement), 'delete') !== false) && $stmt->get_result() === false) {
		// If appropriate return how many rows were affected
		return $stmt->affected_rows;
	}
	$rows = array();
	$result = $stmt->get_result();
	if ($result === FALSE) {
		return NULL;
	}
	while ($row = $result->fetch_object()) {
        array_push($rows, $row);
    }
	if (count($rows) === 0) {
		return NULL;
	}
	return $rows;
}
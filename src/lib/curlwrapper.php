<?php
include_once(__DIR__ . '/conf.php');

class HttpResponse {
	public $code;
	public $status;
	public $header;
	public $body;
	
	public function __construct($string_response) {
		$space_split = explode(" ", $string_response);
		$double_line_split = explode("\r\n\r\n", $string_response);
		
		if (count($space_split) > 3) {
			$this->code = $space_split[1];
			$this->status = explode("\r\n", $space_split[2])[0];
		} else {
			echo "Invalid http response";
			throw new Exception("Invalid http response");
		}
		if (count($double_line_split) === 2) {
			$this->body = $double_line_split[1];
			$line_split = explode("\r\n", $double_line_split[0]);
			if (count($line_split) > 1) {
				$this->header = array();
				$count = 0;
				foreach ($line_split as $value) {
					if ($count > 0) {
						$header_line = explode(': ', $value);
						if (count($header_line) === 2) {
							$this->header[$header_line[0]] = $header_line[1];
						}
					}
					++$count;
				}
			}
		} else {
			echo "Invalid http response";
			throw new Exception("Invalid http response");
		}
	}
	public function toString() {
		$stringOutput = "Code: " . $this->code . "<br>";
		$stringOutput .= "Status: " . $this->status . "<br>";
		$stringOutput .= "Headers: <br>";
		foreach ($this->header as $key => $value) {
			$stringOutput .= "&nbsp;&nbsp;" . $key . ": " . $value . "<br>";
		}
		$stringOutput .= "Body: " . $this->body;
		return $stringOutput;
	}
}

// $url string to get
// $headers is associative array of $headername => $headervalue
function httpGet($url, $headers) {
	$conn = getMysqliConnection();
	$result = query($conn, 'SELECT next_request FROM rate_limit','',array());
	if (time() >= $result[0]->next_request) {
		//Initialize cURL.
		$ch = curl_init();

		//Set the URL that you want to GET by using the CURLOPT_URL option.
		curl_setopt($ch, CURLOPT_URL, $url);

		//Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// Include header in output
		curl_setopt($ch, CURLOPT_HEADER, true);

		// Format headers in to array of strings
		$formatted_headers = array();
		foreach ($headers as $key => $value) {
			$headerstring = $key . ": " . $value;
			array_push($formatted_headers, $headerstring);
		}
		// Add headers to request
		curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted_headers);

		//Execute the request.
		$data = curl_exec($ch);

		//Close the cURL handle.
		curl_close($ch);

		//Print the data out onto the page.
		$response = new HttpResponse($data);
		if ($response->code == 429) {
			file_put_contents('../log/rate_limiting', time() . ': Too many requests. GET - ' . $url, FILE_APPEND);
			$next_request = time() + intval($response->header['Retry-After']) + 1;
			query($conn, 'UPDATE rate_limit SET next_request = ?', 'i', array($next_request));
			$conn->close();
		}

		return $response;
	} else {
		$conn->close();
		header('HTTP/1.0 429 Too Many Requests');
		echo 'Too many requests';
		exit();
	}
}



function httpPost($url, $headers, $data) {
	$conn = getMysqliConnection();
	$result = query($conn, 'SELECT next_request FROM rate_limit','',array());
	if (time() >= $result[0]->next_request) {
		//Initialize cURL.
		$ch = curl_init();

		//Set the URL that you want to GET by using the CURLOPT_URL option.
		curl_setopt($ch, CURLOPT_URL, $url);

		//Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// Include header in output
		curl_setopt($ch, CURLOPT_HEADER, true);

		// Format headers in to array of strings
		$formatted_headers = array();
		foreach ($headers as $key => $value) {
			$headerstring = $key . ": " . $value;
			array_push($formatted_headers, $headerstring);
		}
		// Add headers to request
		curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted_headers);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		//Execute the request.
		$result = curl_exec($ch);

		//Close the cURL handle.
		curl_close($ch);

		//Print the data out onto the page.
		$response = new HttpResponse($result);
		if ($response->code == 429) {
			file_put_contents('../log/rate_limiting', time() . ': Too many requests. POST - ' . $url, FILE_APPEND);
			$next_request = time() + intval($response->header['Retry-After']) + 1;
			$conn = getMysqliConnection();
			query($conn, 'UPDATE rate_limit SET next_request = ?', 'i', array($next_request));
			$conn->close();
		}
		return $response;
	} else {
		$conn->close();
		header('HTTP/1.0 429 Too Many Requests');
		echo 'Too many requests';
		exit();
	}
}
function httpPut($url, $headers, $data) {
    $conn = getMysqliConnection();
	$result = query($conn, 'SELECT next_request FROM rate_limit','',array());
	if (time() >= $result[0]->next_request) {

		//Initialize cURL.
		$ch = curl_init();

		//Set the URL that you want to GET by using the CURLOPT_URL option.
		curl_setopt($ch, CURLOPT_URL, $url);

		//Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// Include header in output
		curl_setopt($ch, CURLOPT_HEADER, true);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		if ($data !== NULL) {
			$data = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$headers['Content-Type'] = 'application/json';
			$headers['Content-Length'] = strlen($data);
		} else {
			$headers['Content-Length'] = 0;
		}

		// Format headers in to array of strings
		$formatted_headers = array();
		foreach ($headers as $key => $value) {
			$headerstring = $key . ": " . $value;
			array_push($formatted_headers, $headerstring);
		}
		// Add headers to request
		curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted_headers);



		//Execute the request.
		$result = curl_exec($ch);

		if ($result === false) {
			throw new Exception(curl_error($ch));
		}

		//Close the cURL handle.
		curl_close($ch);

		//Print the data out onto the page.
		$response = new HttpResponse($result);
		if ($response->code == 429) {
			file_put_contents('../log/rate_limiting', time() . ': Too many requests. PUT - ' . $url, FILE_APPEND);
			$next_request = time() + intval($response->header['Retry-After']) + 1;
			$conn = getMysqliConnection();
			query($conn, 'UPDATE rate_limit SET next_request = ?', 'i', array($next_request));
			$conn->close();
		}
		return $response;
	} else {
		$conn->close();
		header('HTTP/1.0 429 Too Many Requests');
		echo 'Too many requests';
		exit();
	}
}

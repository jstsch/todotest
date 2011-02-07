<?php

/* quick 'n dirty webserver for RedBean (BeanCan-JSON bridge)
it only likes POST / HTTP/1.1 messages (with \r\n newlines)
made for nodejs, untested, experimental (but fun)
*/


if ($argc > 2) die("Usage: php server.php [PORT]\n");
if ($argc == 1) $port = 6656;
if ($argc == 2) {
	$port = (int)$argv[1];
	if ($port == 0) die("Invalid port.\n");
}

// init redbean
require("lib/rb.php"); 
R::setup();
class Model_Todo extends RedBean_SimpleModel {
	public function getList() {
		return R::findAndExport("todo");
	}
}
R::freeze(true);
$beancan = new RedBean_BeanCan;

// turn on to see posted body
$verbose = false;

// prevent date warnings
date_default_timezone_set('UTC');

$socket = stream_socket_server("tcp://0.0.0.0:$port", $errno, $errstr);
if (!$socket) {
	echo "$errstr ($errno)<br />\n";
} else {
	echo "RedBean HTTPServer running, accepting connections on port $port...\n";
	$peer = '';
	while ($client = stream_socket_accept($socket, -1, &$peer)) {
		echo strftime('%c')." UTC - connection from ".$peer."\n";
		$buffer = '';
		while(($pos = strpos($buffer, "\r\n\r\n")) === false) {
			$buffer .= fread($client, 2046); 
		}
		$headers = substr($buffer, 0, $pos);
		$body = substr($buffer, $pos + 4);

		if (strpos($headers, 'POST / HTTP/1.1') !== 0) {
			fwrite($client,  "HTTP/1.0 501 Not Implemented\n"
				. "Connection: close\r\n"
				. "Content-Type: text/plain\r\n"
				. "\r\n"
				. "501 - Not Implemented (server requires a HTTP/1.1 POST to /)");
			fclose($client);
		} else {
			$headers = explode("\n", $headers);
			$cl = -1;
			foreach ($headers as $header) {
				$header = explode(":", $header);
				if (isset($header[0]) && isset($header[1]) && $header[0] == 'Content-Length' && is_numeric(trim($header[1]))) $cl = trim($header[1]);
			}
			if ($cl == -1) {
				if ($verbose) echo "501 - Not Implemented (Content-Length header missing)\n";
				fwrite($client,  "HTTP/1.0 501 Not Implemented\n"
					. "Connection: close\r\n"
					. "Content-Type: text/plain\r\n"
					. "\r\n"
					. "501 - Not Implemented (Content-Length header missing)");
				fclose($client);
			} else {
				while (strlen($body) < $cl && ($reader = fread($client, 2046))) {
					$body .= $reader;
				}
				if ($verbose) echo $body."\n";
				$body = explode("&", $body);
				$response = false;
				foreach ($body as $b) {
					$b = explode("=", $b);
					if (isset($b[0]) && $b[0] == 'json') {
						if (isset($b[1])) $response = $beancan->handleJSONRequest( $b[1] );
					}
				}
				if ($response) {
					// Respond to client
					fwrite($client,  "HTTP/1.0 200 OK\r\n"
						. "Connection: close\r\n"
						. "Content-Type: application/json\r\n"
						. "\r\n"
						. $response);
					fclose($client);	
				} else {
					fwrite($client,  "HTTP/1.0 501 Not Implemented\n"
						. "Connection: close\r\n"
						. "Content-Type: text/plain\r\n"
						. "\r\n"
						. "501 - Not Implemented (sorry, i want proper json)");
					fclose($client);
				}
			}

		}

	}
	fclose($socket);
}
?>
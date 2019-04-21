#!/usr/bin/env php
<?php declare(strict_types=1);
require_once(__DIR__ . '/conf.php');
require_once(__DIR__ . '/db.php');
require_once(__DIR__ . '/spotify.php');


	$conn = getMysqliConnection();
	$result = query($conn, 'SELECT started, finished FROM syncs', '', array());
	if ($result[0]->finished !== null) {
		query($conn, 'UPDATE syncs SET started = ?, finished = null', 'i', array(time()));
		$results = query($conn, 'SELECT leader, listener FROM friends WHERE listening = TRUE', '', array());
		if ($results !== NULL) {
			$playbacks = array();
			foreach ($results as $row) {
				if (!isset($playbacks[$row->leader])) {
					$playbacks[$row->leader] = getCurrentPlaybackData($conn, $row->leader);
				}
				if (!isset($playbacks[$row->listener])) {
					$playbacks[$row->listener] = getCurrentPlaybackData($conn, $row->listener);
				}
				if ($playbacks[$row->leader]['is_playing']) {
					$trackURI = $playbacks[$row->leader]['item']['uri'];
					$progress = $playbacks[$row->leader]['progress_ms'];
					$changes = false;
					if (!$playbacks[$row->listener]['is_playing']) {
						$changes = true;
					}
					// check track
					if ($playbacks[$row->leader]['item']['uri'] != $playbacks[$row->listener]['item']['uri']) {
						$changes = true;
					}
					// check progress
					$min_progress = $playbacks[$row->leader]['progress_ms'] - PROGRESS_DIFFERENCE_THRESHOLD;
					$max_progress = $playbacks[$row->leader]['progress_ms'] + PROGRESS_DIFFERENCE_THRESHOLD;
					if ($min_progress > $playbacks[$row->listener]['progress_ms'] || $max_progress < $playbacks[$row->listener]['progress_ms']) {
						$changes = true;
					}
					// Adjust as necessary
					if ($changes) {
						echo time() . ': Adjusting ' . $row->listener . ' to ' . $row->leader . "\n";
						playTrack($conn, $row->listener, $trackURI, $progress);
					}
				} else {
					if ($playbacks[$row->listener]['is_playing']) {
						// Pause listener
						pause($conn, $row->listener);
					}
				}
			}
		}
		query($conn, 'UPDATE syncs SET finished = ?', 'i', array(time()));
	} else {
		echo time() . ": Last sync not finished\n";
	}
	$conn->close();

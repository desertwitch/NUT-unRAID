<?PHP
/* Copyright Derek Macias
 * Copyright macester
 * Copyright gfjardim
 * Copyright SimonF
 * Copyright desertwitch
 *
 * Copyright Dan Landon
 * Copyright Bergware International
 * Copyright Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 */
exec("timeout -s9 30 /usr/local/emhttp/plugins/nut-dw/scripts/debug", $debug_result);
if(!empty($debug_result)) {
	if(strpos(end($debug_result), "DONE:") !== false) {
		$debugFile = explode(":", end($debug_result))[1];
		if(!empty($debugFile) && file_exists($debugFile)) {
			header("Content-Disposition: attachment; filename=\"" . basename($debugFile) . "\"");
			header("Content-Type: application/octet-stream");
			header("Content-Length: " . filesize($debugFile));
			header("Connection: close");
			readfile($debugFile);
			exit;
		} else {
			echo("ERROR: The NUT Debug Package Generation Script has failed - bash backend returned filename, php could not find file.");
		}
	} else { 
		echo("ERROR: The NUT Debug Package Generation Script has failed - response from the bash backend:<br><pre>");
		echo(implode("\n",$debug_result));
		echo("</pre>");
	}
} else {
	echo("ERROR: The NUT Debug Package Generation Script has failed - no response from the bash backend.");
}
?>

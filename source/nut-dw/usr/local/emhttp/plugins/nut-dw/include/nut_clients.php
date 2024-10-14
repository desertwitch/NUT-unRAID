<?
/* Copyright Derek Macias (parts of code from NUT package)
 * Copyright macester (parts of code from NUT package)
 * Copyright gfjardim (parts of code from NUT package)
 * Copyright SimonF (parts of code from NUT package)
 * Copyright Dan Landon (parts of code from Web GUI)
 * Copyright Bergware International (parts of code from Web GUI)
 * Copyright Lime Technology (any and all other parts of Unraid)
 *
 * Copyright desertwitch (as author and maintainer of this file)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 */
require_once '/usr/local/emhttp/plugins/nut-dw/include/nut_config.php';

$nutc_response = [];

try {
    if($nut_running && $nut_manual == "disable" && $nut_mode == "netserver") {
        exec("/usr/bin/upsc -c ".escapeshellarg($nut_name)."@".escapeshellarg($nut_ip)." 2>/dev/null", $nutc_rows);
        if(!empty($nutc_rows)) {
            $nutc_response["success"]["response"] = "- " . implode("<br>- ", array_map('htmlspecialchars', $nutc_rows));
        } else {
            $nutc_response["error"]["response"] = "NUT upsc has come back with a null response.";
        }
    } else {
        $nutc_response["error"]["response"] = "NUT not running or not in GUI netserver mode.";
    }
}
catch (\Exception $e) {
    error_log($e);
    $nutc_response = [];
    $nutc_response["error"]["response"] = $e->getMessage();
}

echo(json_encode($nutc_response));
?>

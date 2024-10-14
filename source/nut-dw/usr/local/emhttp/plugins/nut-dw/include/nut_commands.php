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
$resp = [];

try {
    if(isset($_POST["nutcmdname"]) && isset($_POST["nutcmduser"]) && isset($_POST["nutcmdpass"]))
    {
        $nutcmd = escapeshellarg($_POST["nutcmdname"]);
        $nutcmd_user = escapeshellarg($_POST["nutcmduser"]);
        $nutcmd_pass = escapeshellarg($_POST["nutcmdpass"]);
        $nutcmd_ups = escapeshellarg($nut_name);
        $nutcmd_ip = escapeshellarg($nut_ip);
        $nutcmd_arg = (isset($_POST["nutcmdarg"]) && !empty($_POST["nutcmdarg"])) ? escapeshellarg($_POST["nutcmdarg"]) : false;

        $nutcmd_retval = -1;
        $nutcmd_retarr = [];

        if(isset($_POST["nutcmdwait"])) {
            if($nutcmd_arg) {
                exec("upscmd -u $nutcmd_user -p $nutcmd_pass -w $nutcmd_ups@$nutcmd_ip $nutcmd $nutcmd_arg 2>&1", $nutcmd_retarr, $nutcmd_retval);
            } else {
                exec("upscmd -u $nutcmd_user -p $nutcmd_pass -w $nutcmd_ups@$nutcmd_ip $nutcmd 2>&1", $nutcmd_retarr, $nutcmd_retval);
            }
            if($nutcmd_retval === 0) {
                $resp["success"]["response"] = htmlspecialchars(implode(PHP_EOL, $nutcmd_retarr) ?? "");
            } else {
                $resp["error"]["response"] = htmlspecialchars(implode(PHP_EOL, $nutcmd_retarr) ?? "");
            }
        } else {
            if($nutcmd_arg) {
                exec("upscmd -u $nutcmd_user -p $nutcmd_pass $nutcmd_ups@$nutcmd_ip $nutcmd $nutcmd_arg 2>&1", $nutcmd_retarr, $nutcmd_retval);
            } else {
                exec("upscmd -u $nutcmd_user -p $nutcmd_pass $nutcmd_ups@$nutcmd_ip $nutcmd 2>&1", $nutcmd_retarr, $nutcmd_retval);
            }
            if($nutcmd_retval === 0) {
                $resp["success"]["response"] = htmlspecialchars(implode(PHP_EOL, $nutcmd_retarr) ?? "");
            } else {
                $resp["error"]["response"] = htmlspecialchars(implode(PHP_EOL, $nutcmd_retarr) ?? "");
            }
        }
    } else {
        $resp["error"]["response"] = "Missing required POST variables!";
    }
}
catch (\Throwable $t) {
    error_log($t);
    $resp = [];
    $resp["error"]["response"] = $t->getMessage();
}
catch (\Exception $e) {
    error_log($e);
    $resp = [];
    $resp["error"]["response"] = $e->getMessage();
}

echo(json_encode($resp));
?>

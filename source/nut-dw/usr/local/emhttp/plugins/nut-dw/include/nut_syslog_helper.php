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
$logFile = "/boot/logs/syslog";
if(isset($_GET["dl"]) && $_GET["dl"] == "true" && file_exists($logFile)) {
    header("Content-Disposition: attachment; filename=\"" . basename($logFile) . ".log\"");
    header("Content-Type: application/octet-stream");
    header("Content-Length: " . filesize($logFile));
    header("Connection: close");
    readfile($logFile);
    exit;
}
require_once '/usr/local/emhttp/plugins/nut-dw/include/nut_config.php';
?>
<h1><?=$logFile?> (last 90 lines - <a href="/plugins/nut-dw/include/nut_syslog_helper.php?dl=true">download full logfile</a>)</h1>
<div>If information on this page is missing or outdated, please check if syslog mirroring is activated (<em>Settings->Syslog Server->Mirror syslog to flash</em>).</div>
<div>Do not forget to disable syslog mirroring after obtaining the required diagnostic information, as this setting will increase wear on your USB drive.</div>
<br>
<div><strong>WARNING:</strong> Log files can contain <strong>sensitive information</strong> - please <strong>copy only the relevant lines when sharing</strong> with others!</div>
<hr>
<pre>
<?=nut_tailFile($logFile);?>
</pre>

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
?>
<h1>/boot/logs/syslog</h1>
<div>If information on this page is missing or outdated, please check if syslog mirroring is activated (<em>Settings->Syslog Server->Mirror syslog to flash</em>).</div>
<div>Do not forget to disable syslog mirroring after obtaining the required diagnostic information, as this setting will increase wear on your USB drive.</div>
<br>
<div><strong>WARNING:</strong> Log files can contain <strong>sensitive information</strong> - please <strong>copy only the relevant lines when sharing</strong> with others!</div>
<hr>
<pre>
<?=file_exists("/boot/logs/syslog")?htmlspecialchars(file_get_contents("/boot/logs/syslog") ?? ""):"no syslog mirror found on USB - is syslog mirroring activated ?"?>
</pre>

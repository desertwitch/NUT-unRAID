<h1>/boot/logs/syslog</h1>
<div>If information on this page is missing or outdated, please check if syslog mirroring is activated (<em>Settings->Syslog Server->Mirror syslog to flash</em>).</div>
<div>Do not forget to disable syslog mirroring after obtaining the required diagnostic information, as this setting will increase wear on your USB drive.</div>
<br>
<div><strong>WARNING:</strong> Log files can contain <strong>sensitive information</strong> - please <strong>copy only the relevant lines when sharing</strong> with others!</div>
<hr>
<pre>
<?=file_exists("/boot/logs/syslog")?file_get_contents("/boot/logs/syslog"):"no syslog mirror found on USB - is syslog mirroring activated ?"?>
</pre>

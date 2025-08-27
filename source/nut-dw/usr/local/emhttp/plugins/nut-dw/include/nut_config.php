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
require_once '/usr/local/emhttp/plugins/nut-dw/include/nut_helpers.php';

$sName = "nut-dw";
$nut_cfg                = parse_ini_file("/boot/config/plugins/$sName/$sName.cfg");
$nut_service            = trim(isset($nut_cfg['SERVICE'])             ? htmlspecialchars($nut_cfg['SERVICE'])               : 'disable');
$nut_power              = trim(isset($nut_cfg['POWER'])               ? htmlspecialchars($nut_cfg['POWER'])                 : 'auto');
$nut_powerva            = trim(isset($nut_cfg['POWERVA'])             ? htmlspecialchars($nut_cfg['POWERVA'])               : 0);
$nut_powerw             = trim(isset($nut_cfg['POWERW'])              ? htmlspecialchars($nut_cfg['POWERW'])                : 0);
$nut_manual             = trim(isset($nut_cfg['MANUAL'])              ? htmlspecialchars($nut_cfg['MANUAL'])                : 'disable');
$nut_syslog_method      = trim(isset($nut_cfg['SYSLOGMETHOD'])        ? htmlspecialchars($nut_cfg['SYSLOGMETHOD'])          : 'syslog');
$nut_syslog_filter      = trim(isset($nut_cfg['SYSLOGFILTER'])        ? htmlspecialchars($nut_cfg['SYSLOGFILTER'])          : 'disable');
$nut_syslog_backup      = trim(isset($nut_cfg['SYSLOGBACKUP'])        ? htmlspecialchars($nut_cfg['SYSLOGBACKUP'])          : 'disable');
$nut_usb_override       = trim(isset($nut_cfg['ORUSBPOWER'])          ? htmlspecialchars($nut_cfg['ORUSBPOWER'])            : 'disable');
$nut_name               = trim(isset($nut_cfg['NAME'])                ? htmlspecialchars($nut_cfg['NAME'])                  : 'ups');
$nut_monuser            = trim(isset($nut_cfg['MONUSER'])             ? htmlspecialchars($nut_cfg['MONUSER'])               : 'monuser');
$nut_monpass            = trim(isset($nut_cfg['MONPASS'])             ? htmlspecialchars($nut_cfg['MONPASS'])               : base64_encode('monpass'));
$nut_slaveuser          = trim(isset($nut_cfg['SLAVEUSER'])           ? htmlspecialchars($nut_cfg['SLAVEUSER'])             : 'slaveuser');
$nut_slavepass          = trim(isset($nut_cfg['SLAVEPASS'])           ? htmlspecialchars($nut_cfg['SLAVEPASS'])             : base64_encode('slavepass'));
$nut_driver             = trim(isset($nut_cfg['DRIVER'])              ? htmlspecialchars($nut_cfg['DRIVER'])                : 'usbhid-ups');
$nut_serial             = trim(isset($nut_cfg['SERIAL'])              ? htmlspecialchars($nut_cfg['SERIAL'])                : '');
$nut_port               = trim(isset($nut_cfg['PORT'])                ? htmlspecialchars($nut_cfg['PORT'])                  : 'auto');
$nut_deblevel           = trim(isset($nut_cfg['DEBLEVEL'])            ? htmlspecialchars($nut_cfg['DEBLEVEL'])              : 'default');
$nut_deblevel_mon       = trim(isset($nut_cfg['DEBLEVELMON'])         ? htmlspecialchars($nut_cfg['DEBLEVELMON'])           : 'default');
$nut_ip                 = trim(isset($nut_cfg['IPADDR'])              ? htmlspecialchars($nut_cfg['IPADDR'])                : '127.0.0.1');
$nut_mode               = trim(isset($nut_cfg['MODE'])                ? htmlspecialchars($nut_cfg['MODE'])                  : 'standalone');
$nut_shutdown           = trim(isset($nut_cfg['SHUTDOWN'])            ? htmlspecialchars($nut_cfg['SHUTDOWN'])              : 'sec_timer');
$nut_battery            = trim(isset($nut_cfg['BATTERYLEVEL'])        ? htmlspecialchars($nut_cfg['BATTERYLEVEL'])          : 20);
$nut_seconds            = trim(isset($nut_cfg['SECONDS'])             ? htmlspecialchars($nut_cfg['SECONDS'])               : 240);
$nut_timeout            = trim(isset($nut_cfg['TIMEOUT'])             ? htmlspecialchars($nut_cfg['TIMEOUT'])               : 240);
$nut_upskill            = trim(isset($nut_cfg['UPSKILL'])             ? htmlspecialchars($nut_cfg['UPSKILL'])               : 'disable');
$nut_replbattmsg        = trim(isset($nut_cfg['REPLBATTMSG'])         ? htmlspecialchars($nut_cfg['REPLBATTMSG'])           : 'disable');
$nut_poll               = trim(isset($nut_cfg['POLL'])                ? htmlspecialchars($nut_cfg['POLL'])                  : 15);
$nut_snmpver            = trim(isset($nut_cfg['SNMPVER'])             ? htmlspecialchars($nut_cfg['SNMPVER'])               : 'v2c');
$nut_community          = trim(isset($nut_cfg['COMMUNITY'])           ? htmlspecialchars($nut_cfg['COMMUNITY'])             : 'public');
$nut_snmpmib            = trim(isset($nut_cfg['SNMPMIB'])             ? htmlspecialchars($nut_cfg['SNMPMIB'])               : 'auto');
$nut_footer             = trim(isset($nut_cfg['FOOTER'])              ? htmlspecialchars($nut_cfg['FOOTER'])                : 'disable');
$nut_footer_style       = trim(isset($nut_cfg['FOOTER_STYLE'])        ? htmlspecialchars($nut_cfg['FOOTER_STYLE'])          : '0');
$nut_footer_conns       = trim(isset($nut_cfg['FOOTER_CONNS'])        ? htmlspecialchars($nut_cfg['FOOTER_CONNS'])          : 'enable');
$nut_refresh            = trim(isset($nut_cfg['REFRESH'])             ? htmlspecialchars($nut_cfg['REFRESH'])               : 'disable');
$nut_interval           = trim(isset($nut_cfg['INTERVAL'])            ? htmlspecialchars($nut_cfg['INTERVAL'])              : 15 );
$nut_runtime            = trim(isset($nut_cfg['RUNTIME'])             ? htmlspecialchars($nut_cfg['RUNTIME'])               : 'battery.runtime');
$nut_backend            = trim(isset($nut_cfg['BACKEND'])             ? htmlspecialchars($nut_cfg['BACKEND'])               : 'default');
$nut_commands           = trim(isset($nut_cfg['COMMANDS'])            ? htmlspecialchars($nut_cfg['COMMANDS'])              : 'enable');
$nut_statistics         = trim(isset($nut_cfg['STATISTICS'])          ? htmlspecialchars($nut_cfg['STATISTICS'])            : 'disable');
$nut_stats_poll         = trim(isset($nut_cfg['STATSPOLL'])           ? htmlspecialchars($nut_cfg['STATSPOLL'])             : '30min');
$nut_stats_override     = trim(isset($nut_cfg['STATSOVERRIDE'])       ? htmlspecialchars($nut_cfg['STATSOVERRIDE'])         : 'disable');
$nut_stats_c1_var       = trim(isset($nut_cfg['STATSCHART1VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART1VAR'])        : 'ups.realpower');
$nut_stats_c1_txt       = trim(isset($nut_cfg['STATSCHART1TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART1TXT'])        : 'Power Draw (in W)');
$nut_stats_c2_var       = trim(isset($nut_cfg['STATSCHART2VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART2VAR'])        : 'battery.charge');
$nut_stats_c2_txt       = trim(isset($nut_cfg['STATSCHART2TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART2TXT'])        : 'Battery Charge (in %)');
$nut_stats_c3_var       = trim(isset($nut_cfg['STATSCHART3VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART3VAR'])        : 'battery.voltage');
$nut_stats_c3_txt       = trim(isset($nut_cfg['STATSCHART3TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART3TXT'])        : 'Battery Voltage (in V)');
$nut_stats_c4_var       = trim(isset($nut_cfg['STATSCHART4VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART4VAR'])        : 'input.voltage');
$nut_stats_c4_txt       = trim(isset($nut_cfg['STATSCHART4TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART4TXT'])        : 'Input Voltage (in V)');
$nut_stats_c5_var       = trim(isset($nut_cfg['STATSCHART5VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART5VAR'])        : 'input.frequency');
$nut_stats_c5_txt       = trim(isset($nut_cfg['STATSCHART5TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART5TXT'])        : 'Input Frequency (in Hz)');
$nut_stats_c6_var       = trim(isset($nut_cfg['STATSCHART6VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART6VAR'])        : 'output.voltage');
$nut_stats_c6_txt       = trim(isset($nut_cfg['STATSCHART6TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART6TXT'])        : 'Output Voltage (in V)');
$nut_stats_c7_var       = trim(isset($nut_cfg['STATSCHART7VAR'])      ? htmlspecialchars($nut_cfg['STATSCHART7VAR'])        : 'output.frequency');
$nut_stats_c7_txt       = trim(isset($nut_cfg['STATSCHART7TXT'])      ? htmlspecialchars($nut_cfg['STATSCHART7TXT'])        : 'Output Frequency (in Hz)');
$nut_loadcalc           = trim(isset($nut_cfg['LOADCALC'])            ? htmlspecialchars($nut_cfg['LOADCALC'])              : 'disable');
$nut_loadunit           = trim(isset($nut_cfg['LOADUNIT'])            ? htmlspecialchars($nut_cfg['LOADUNIT'])              : 'W');
$nut_rtunit             = trim(isset($nut_cfg['RTUNIT'])              ? htmlspecialchars($nut_cfg['RTUNIT'])                : 'seconds');

$nut_running      = !empty(shell_exec("pgrep -x upsmon 2>/dev/null"));
$apc_running      = !empty(shell_exec("pgrep -x apcupsd 2>/dev/null"));

$nut_installed_backend = htmlspecialchars(trim(shell_exec("find /var/log/packages/ -type f -iname 'nut*' ! -iname 'nut-plugin*' -printf '%f\n' 2>/dev/null") ?? "n/a"));

# debug constant to overwrite ups.status
// define('NUT_STATUS_DEBUG', 'OB DISCHRG BYPASS CAL');
$nut_states = [
    'OL'      => ['severity' => 0, 'msg' => 'On Line'],
    'OB'      => ['severity' => 1, 'msg' => 'On Battery'],
    'BYPASS'  => ['severity' => 1, 'msg' => 'On Bypass'],
    'CHRG'    => ['severity' => 0, 'msg' => 'Charging Battery'],
    'DISCHRG' => ['severity' => 0, 'msg' => 'Discharging Battery'],
    'LB'      => ['severity' => 2, 'msg' => 'Low Battery'],
    'HB'      => ['severity' => 2, 'msg' => 'High Battery'],
    'RB'      => ['severity' => 2, 'msg' => 'Replace Battery'],
    'CAL'     => ['severity' => 0, 'msg' => 'Calibrating'],
    'OVER'    => ['severity' => 2, 'msg' => 'Overloaded'],
    'TRIM'    => ['severity' => 0, 'msg' => 'Trimming Input'],
    'BOOST'   => ['severity' => 0, 'msg' => 'Boosting Input'],
    'OFF'     => ['severity' => 2, 'msg' => 'UPS is Offline'],
    'FSD'     => ['severity' => 2, 'msg' => 'Forcing Shutdown'],
    'ALARM'   => ['severity' => 2, 'msg' => 'Active Alarm(s)'],
    'ECO'     => ['severity' => 0, 'msg' => 'Eco Mode'],
    'HE'      => ['severity' => 0, 'msg' => 'High Efficiency Mode'],
];
$nut_msgSeverity = [
    0 => ['label' => 'info',    'css_class' => 'green-text'],
    1 => ['label' => 'warning', 'css_class' => 'orange-text'],
    2 => ['label' => 'error',   'css_class' => 'red-text'],
];
?>

<?
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
require_once '/usr/local/emhttp/plugins/nut-dw/include/nut_helpers.php';

$sName = "nut-dw";
$nut_cfg          = parse_ini_file("/boot/config/plugins/$sName/$sName.cfg");
$nut_service      = isset($nut_cfg['SERVICE'])      ? htmlspecialchars($nut_cfg['SERVICE'])       : 'disable';
$nut_power        = isset($nut_cfg['POWER'])        ? htmlspecialchars($nut_cfg['POWER'])         : 'auto';
$nut_powerva      = isset($nut_cfg['POWERVA'])      ? intval($nut_cfg['POWERVA'])                 : 0;
$nut_powerw       = isset($nut_cfg['POWERW'])       ? intval($nut_cfg['POWERW'])                  : 0;
$nut_manual       = isset($nut_cfg['MANUAL'])       ? htmlspecialchars($nut_cfg['MANUAL'])        : 'disable';
$nut_usb_override = isset($nut_cfg['ORUSBPOWER'])   ? htmlspecialchars($nut_cfg['ORUSBPOWER'])    : 'disable'; 
$nut_name         = isset($nut_cfg['NAME'])         ? htmlspecialchars($nut_cfg['NAME'])          : 'ups';
$nut_monuser      = isset($nut_cfg['MONUSER'])      ? htmlspecialchars($nut_cfg['MONUSER'])       : 'monuser';
$nut_monpass      = isset($nut_cfg['MONPASS'])      ? htmlspecialchars($nut_cfg['MONPASS'])       : base64_encode('monpass');
$nut_slaveuser    = isset($nut_cfg['SLAVEUSER'])    ? htmlspecialchars($nut_cfg['SLAVEUSER'])     : 'slaveuser';
$nut_slavepass    = isset($nut_cfg['SLAVEPASS'])    ? htmlspecialchars($nut_cfg['SLAVEPASS'])     : base64_encode('slavepass');
$nut_driver       = isset($nut_cfg['DRIVER'])       ? htmlspecialchars($nut_cfg['DRIVER'])        : 'custom';
$nut_serial       = isset($nut_cfg['SERIAL'])       ? htmlspecialchars($nut_cfg['SERIAL'])        : 'none';
$nut_port         = isset($nut_cfg['PORT'])         ? htmlspecialchars($nut_cfg['PORT'])          : 'auto';
$nut_deblevel     = isset($nut_cfg['DEBLEVEL'])     ? htmlspecialchars($nut_cfg['DEBLEVEL'])      : 'default';
$nut_deblevel_mon = isset($nut_cfg['DEBLEVELMON'])  ? htmlspecialchars($nut_cfg['DEBLEVELMON'])   : 'default';
$nut_ip           = preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $nut_cfg['IPADDR']) ? htmlspecialchars($nut_cfg['IPADDR']) : '127.0.0.1';
$nut_mode         = isset($nut_cfg['MODE'])         ? htmlspecialchars($nut_cfg['MODE'])          : 'standalone';
$nut_shutdown     = isset($nut_cfg['SHUTDOWN'])     ? htmlspecialchars($nut_cfg['SHUTDOWN'])      : 'sec_timer';
$nut_battery      = isset($nut_cfg['BATTERYLEVEL']) ? intval($nut_cfg ['BATTERYLEVEL'])           : 20;
$nut_seconds      = isset($nut_cfg['SECONDS'])      ? intval($nut_cfg ['SECONDS'])                : 240;
$nut_timeout      = isset($nut_cfg['TIMEOUT'])      ? intval($nut_cfg ['TIMEOUT'])                : 240;
$nut_upskill      = isset($nut_cfg['UPSKILL'])      ? htmlspecialchars($nut_cfg ['UPSKILL'])      : 'disable';
$nut_replbattmsg  = isset($nut_cfg['REPLBATTMSG'])  ? htmlspecialchars($nut_cfg ['REPLBATTMSG'])  : 'disable';
$nut_poll         = isset($nut_cfg['POLL'])         ? intval($nut_cfg ['POLL'])                   : 15;
$nut_community    = isset($nut_cfg['COMMUNITY'])    ? htmlspecialchars($nut_cfg ['COMMUNITY'])    : 'public';
$nut_footer       = isset($nut_cfg['FOOTER'])       ? htmlspecialchars($nut_cfg ['FOOTER'])       : 'disable';
$nut_footer_style = isset($nut_cfg['FOOTER_STYLE']) ? htmlspecialchars($nut_cfg ['FOOTER_STYLE']) : '0';
$nut_refresh      = isset($nut_cfg['REFRESH'])      ? htmlspecialchars($nut_cfg ['REFRESH'])      : 'disable';
$nut_interval     = isset($nut_cfg['INTERVAL'])     ? intval($nut_cfg['INTERVAL'])                : 15 ;
$nut_runtime      = isset($nut_cfg['RUNTIME'])      ? htmlspecialchars($nut_cfg ['RUNTIME'])      : 'battery.runtime';
$nut_backend      = isset($nut_cfg['BACKEND'])      ? htmlspecialchars($nut_cfg['BACKEND'])       : 'default';
$nut_statistics   = isset($nut_cfg['STATISTICS'])   ? htmlspecialchars($nut_cfg ['STATISTICS'])   : 'disable';
$nut_stats_poll   = isset($nut_cfg['STATSPOLL'])    ? htmlspecialchars($nut_cfg ['STATSPOLL'])    : '30min';
$nut_stats_override = isset($nut_cfg['STATSOVERRIDE'])     ? htmlspecialchars($nut_cfg ['STATSOVERRIDE'])       : 'disable';
$nut_stats_c1_var = isset($nut_cfg['STATSCHART1VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART1VAR'])      : 'ups.realpower';
$nut_stats_c1_txt = isset($nut_cfg['STATSCHART1TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART1TXT'])      : 'Power Draw (in W)';
$nut_stats_c2_var = isset($nut_cfg['STATSCHART2VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART2VAR'])      : 'battery.charge';
$nut_stats_c2_txt = isset($nut_cfg['STATSCHART2TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART2TXT'])      : 'Battery Charge (in %)';
$nut_stats_c3_var = isset($nut_cfg['STATSCHART3VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART3VAR'])      : 'battery.voltage';
$nut_stats_c3_txt = isset($nut_cfg['STATSCHART3TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART3TXT'])      : 'Battery Voltage (in V)';
$nut_stats_c4_var = isset($nut_cfg['STATSCHART4VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART4VAR'])      : 'input.voltage';
$nut_stats_c4_txt = isset($nut_cfg['STATSCHART4TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART4TXT'])      : 'Input Voltage (in V)';
$nut_stats_c5_var = isset($nut_cfg['STATSCHART5VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART5VAR'])      : 'input.frequency';
$nut_stats_c5_txt = isset($nut_cfg['STATSCHART5TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART5TXT'])      : 'Input Frequency (in Hz)';
$nut_stats_c6_var = isset($nut_cfg['STATSCHART6VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART6VAR'])      : 'output.voltage';
$nut_stats_c6_txt = isset($nut_cfg['STATSCHART6TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART6TXT'])      : 'Output Voltage (in V)';
$nut_stats_c7_var = isset($nut_cfg['STATSCHART7VAR'])      ? htmlspecialchars($nut_cfg ['STATSCHART7VAR'])      : 'output.frequency';
$nut_stats_c7_txt = isset($nut_cfg['STATSCHART7TXT'])      ? htmlspecialchars($nut_cfg ['STATSCHART7TXT'])      : 'Output Frequency (in Hz)';
$nut_rtunit       = isset($nut_cfg['RTUNIT'])              ? htmlspecialchars($nut_cfg ['RTUNIT'])              : 'seconds';

$nut_running      = (intval(trim(shell_exec( "[ -f /proc/`cat /var/run/nut/upsmon.pid 2> /dev/null`/exe ] && echo 1 || echo 0 2> /dev/null" ))) === 1 );
$nut_installed_backend = trim(shell_exec("find /var/log/packages/ -type f -iname 'nut*' ! -iname 'nut-plugin*' -printf '%f\n' 2> /dev/null"));
$nut_plugin_version = trim(shell_exec("/usr/local/sbin/plugin version /boot/config/plugins/nut-dw.plg 2> /dev/null"));

$apc_running      = (intval(trim(shell_exec( "[ -f /proc/`cat /var/run/apcupsd.pid 2> /dev/null`/exe ] && echo 1 || echo 0 2> /dev/null" ))) === 1 );
$powertop_installed = (intval(trim(shell_exec( "[ -n \"`find /var/log/packages/ -type f -iname 'powertop*' -printf '%f\n' 2> /dev/null`\" ] && echo 1 || echo 0 2> /dev/null" ))) === 1 );

# debug constant to overwrite ups.status
// define('NUT_STATUS_DEBUG', 'OB DISCHRG BYPASS CAL');
$nut_states = [
    'OL'      => ['severity' => 0, 'msg' => 'On Line'],
    'OB'      => ['severity' => 1, 'msg' => 'On Battery'],
    'LB'      => ['severity' => 2, 'msg' => 'Low Battery'],
    'HB'      => ['severity' => 2, 'msg' => 'High Battery'],
    'RB'      => ['severity' => 2, 'msg' => 'The battery needs to be replaced'],
    'CHRG'    => ['severity' => 0, 'msg' => 'The battery is charging'],
    'DISCHRG' => ['severity' => 1, 'msg' => 'The battery is discharging'],
    'BYPASS'  => ['severity' => 1, 'msg' => 'UPS bypass circuit is active (no battery protection is available)'],
    'CAL'     => ['severity' => 0, 'msg' => 'UPS is currently performing runtime calibration (on battery)'],
    'OFF'     => ['severity' => 1, 'msg' => 'UPS is offline and is not supplying power to the load'],
    'OVER'    => ['severity' => 2, 'msg' => 'UPS is overloaded'],
    'TRIM'    => ['severity' => 1, 'msg' => 'UPS is trimming incoming voltage (called "buck" in some hardware)'],
    'BOOST'   => ['severity' => 1, 'msg' => 'UPS is boosting incoming voltage'],
    'FSD'     => ['severity' => 2, 'msg' => 'Forced Shutdown'],
];
$nut_msgSeverity = [
    0 => ['label' => 'info',    'css_class' => 'green-text'],
    1 => ['label' => 'warning', 'css_class' => 'orange-text'],
    2 => ['label' => 'error',   'css_class' => 'red-text'],
];
?>

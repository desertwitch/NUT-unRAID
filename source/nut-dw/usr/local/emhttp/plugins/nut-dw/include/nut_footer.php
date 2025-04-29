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

if (!$nut_running) {
    exit(0);
}

$red    = "red-text";
$green  = "green-text";
$orange = "orange-text";
$black  = "black-text";

function nut_get_ups($name, $ip="127.0.0.1")
{
    $output = [];
    $alarm = 0;
    exec("/usr/bin/upsc ".escapeshellarg($name)."@".escapeshellarg($ip)." 2>/dev/null", $rows);
    for ($i=0; $i<count($rows); $i++) {
        $row = array_map('trim', explode(':', $rows[$i], 2));
        $prop = $row[0];
        if (stripos($prop, "ups.alarm")!== false) {
            $prop = "{$prop}".$alarm++;
        }
        $output[$prop] = $row[1];
    }
    return $output;
}

function nut_array_key_exists_wildcard ( $arr, $nee ) {
    $nee = str_replace( '\\*', '.*?', preg_quote( $nee, '/' ) );
    return array_values(preg_grep( '/^' . $nee . '$/i', array_keys( $arr ) ));
}

function nut_format_time($repTime, $repUnit) {
    $t = ($repUnit == "minutes" ? round($repTime*60) : round($repTime));
    return gmdate("H:i:s" ,$t );
}

$nutf_response = [];

try {
    $status = [];
    $ups_status = nut_get_ups($nut_name, $nut_ip);
    if (count($ups_status)) {
        $online           = (array_key_exists("ups.status", $ups_status) ? nut_ups_status([$ups_status["ups.status"]], true) : false );
        $battery          = (array_key_exists("battery.charge",$ups_status)) ? intval(strtok($ups_status['battery.charge'],' ')) : false;
        $load             = (array_key_exists("ups.load", $ups_status)) ? intval(strtok($ups_status['ups.load'],' ')) : 0;
        $realPower        = (array_key_exists("ups.realpower", $ups_status)) ? intval(strtok($ups_status['ups.realpower'],' ')) : NULL;
        $realPowerNominal = (array_key_exists("ups.realpower.nominal", $ups_status)) ? intval(strtok($ups_status['ups.realpower.nominal'],' ')) : NULL;
        $apparentPower    = (array_key_exists("ups.power", $ups_status)) ? intval(strtok($ups_status['ups.power'],' ')) : NULL;
        $powerNominal     = (array_key_exists("ups.power.nominal", $ups_status)) ? intval(strtok($ups_status['ups.power.nominal'],' ')) : NULL;

        if ($nut_power == 'manual') {
            # handle nominal VA
            $manual_powerva = intval($nut_powerva);
            if ($manual_powerva != -1) { # -1 disables override (use UPS nominal)
                $powerNominal = abs($manual_powerva); # Make it positive

                if ($manual_powerva > 0) {
                    # Positive value -> override nominal VA and affect live consumption VA
                    $apparentPower = 0;
                }
                # negative or 0 -> override nominal but do not affect live VA
            }

            # Handle nominal W
            $manual_powerw = intval($nut_powerw);
            if ($manual_powerw != -1) { # -1 disables override (use UPS nominal)
                $realPowerNominal = abs($manual_powerw); # Make it positive

                if ($manual_powerw > 0) {
                    # Positive value -> override nominal W and affect live consumption W
                    $realPower = 0;
                }
                # negative or 0 -> override nominal but do not affect live W
            }
        }

        $ups_alarm = nut_array_key_exists_wildcard($ups_status, '*ups.alarm*');
        if (count($ups_alarm)) {
            $alarms = "<b>NUT Active UPS Alarm(s):</b>";
            foreach ($ups_alarm as $al) {
                $alarms .= "<br>- ".$ups_status[$al];
            }
            $status[0] = "<span id='nut_alarm' class='tooltip-nut $red' data=\"$alarms\"><i class='fa fa-bell faa-ring animated'></i></span>";
        }

        $battery_runtime = array_key_exists($nut_runtime, $ups_status) ? nut_format_time($ups_status[$nut_runtime],$nut_rtunit) : "n/a";
        $css_class = $online['severity'] > 0 ? $nut_msgSeverity[$online['severity']]['css_class'] : ($nut_footer_style == 1 ? $black : $green);
        $fa_icon = '';
        $statusTooltipData = '';
        $batteryText = $battery . "&thinsp;%";

        if ($battery === false) {
            # if no battery info
            $batteryText = " n/a";
            $fa_icon = "fa-battery-empty";
            $online['fulltext'][] = 'Battery info not available';
        } elseif (is_array($online) && in_array('CHRG', $online['value'])) {
            # if ups.status contain CHRG
            $fa_icon = "fa-battery-charging";
        } elseif (is_array($online) && in_array('DISCHRG', $online['value'])) {
            # if ups.status contain DISCHRG
            $fa_icon = "fa-battery-discharging";
            $online['fulltext'][] = "Est. " . $battery_runtime . " left";
        } elseif (is_array($online) && in_array('OB', $online['value'])) {
            # if ups.status contain OB
            $fa_icon = "fa-battery-discharging";
            $online['fulltext'][] = "Est. " . $battery_runtime . " left";
        } elseif (is_array($online) && $online['value']) {
            # other ups.status messages
            $fa_icon = "fa-battery-full";
            # blink battery icon if ups.status contain RB (Replace Battery)
            if (in_array('RB', $online['value'])) $fa_icon .= ' fa-blink';
        } else {
            # unknown status
            $fa_icon = "fa-battery-empty";
            $online['fulltext'][] = 'Battery status unknown';
        }

        $statusTooltipData = ' data="<b>NUT Device Status:</b><br>[' . $nut_name . '] ' . implode(' - ', $online['fulltext']) . '"';

        $status[1] = "<span id='" . ($nut_footer_style == 0 ? "nut_battery" : "") . "' class='".($nut_footer_style == 0 || $online['severity'] > 0 ? "tooltip-nut" : "")." " . $css_class . "'" . $statusTooltipData . "><i class='fa " . $fa_icon . "' style='vertical-align: baseline;'></i>&thinsp;" . $batteryText . "</span>";

        # if no ups.load compute from ups.power(.nominal) or ups.realpower(.nominal)
        # also compute if calculation is otherwise forced by user setting
        if ($load <= 0 || $nut_loadcalc == "enable") {
            $loadW = 0; $loadVA = 0;
            if ($realPower > 0 && $realPowerNominal > 0) $loadW = round($realPower / $realPowerNominal  * 100);
            if ($apparentPower > 0 && $powerNominal > 0) $loadVA = round($apparentPower / $powerNominal  * 100);
            if ($nut_loadunit == "W" && $loadW > 1 && $loadW < 101) $load = $loadW;
            if ($nut_loadunit == "VA" && $loadVA > 1 && $loadVA < 101) $load = $loadVA;
        }

        # if no ups.power compute from load and ups.power.nominal
        if ($apparentPower <= 0) $apparentPower = $powerNominal > 0 && $load ? round($powerNominal * $load * 0.01) : 0;

        # if no ups.realpower compute from load and ups.realpower.nominal (in W)
        if ($realPower <= 0) $realPower = $realPowerNominal > 0 && $load ? round($realPowerNominal * $load * 0.01) : 0;

        $powerText = '';
        $powerTooltipData = '';

        if ($realPower > 0 && $apparentPower > 0) {
            # display load, real and apparent power
            $powerText = "{$realPower}&thinsp;W&thinsp;({$apparentPower}&thinsp;VA)";
            $powerTooltipData = "Load: $load&thinsp;% - Real Power: $realPower&thinsp;W - Apparent Power: $apparentPower&thinsp;VA";
        } elseif ($realPower > 0 && $load) {
            # display load and real power
            $powerText = "{$realPower}&thinsp;W";
            $powerTooltipData = "Load: $load&thinsp;% - Real Power: $realPower&thinsp;W";
        } elseif ($apparentPower > 0 && $load) {
            # display load and apparent power
            $powerText = "{$apparentPower}&thinsp;VA";
            $powerTooltipData = "Load: $load&thinsp;% - Apparent Power: $apparentPower&thinsp;VA";
        } elseif ($load) {
            # display load
            $powerText = "{$load}&thinsp;%";
            $powerTooltipData = "Load: $load&thinsp;%";
        }

        $powerTooltipData = " data='<b>NUT Power Metrics:</b><br>[{$nut_name}] " . $powerTooltipData . "'";

        # show connected clients in netserver mode
        if ($nut_manual == "disable" && $nut_mode == "netserver" && $nut_footer_conns !== "disable") {
            try {
                exec("/usr/bin/upsc -c ".escapeshellarg($nut_name)."@".escapeshellarg($nut_ip)." 2>/dev/null", $nutc_rows);
                if(!empty($nutc_rows)) {
                    $nutc_rows = array_diff($nutc_rows, ["127.0.0.1"]);
                    $nutc_count = count($nutc_rows);
                    if(!empty($nutc_rows)) {
                        $status[3] = "<span id='nut_clients' class='tooltip-nut ".($nut_footer_style == 0 ? "$green" : "$black")."' data=\"<b>NUT Connected Slaves:</b><br>- ".implode("<br>- ",array_map('htmlspecialchars', $nutc_rows))."\"><i class='fa fa-user-circle'></i>&thinsp;$nutc_count</span>";
                    } else {
                        $status[3] = "<span id='nut_clients' class='tooltip-nut ".($nut_footer_style == 0 ? "$green" : "$black")."' data=\"<b>NUT Connected Slaves:</b><br>(No Active Connections)\"><i class='fa fa-user-circle'></i>&thinsp;$nutc_count</span>";
                    }
                }
            }
            catch (\Throwable $t) {
                error_log($t);
                unset($status[3]);
            }
            catch (\Exception $e) {
                error_log($e);
                unset($status[3]);
            }
        }
        $status[2] = "<span id='".($nut_footer_style == 0 ? "nut_power" : "")."' class='".($nut_footer_style == 0 || $load >= 90 ? "tooltip-nut" : "")." " . ($load >= 90 ? $red : ($nut_footer_style == 1 ? $black : $green)) . "'" . $powerTooltipData . "><i class='fa fa-plug'></i>&thinsp;" . $powerText . "</span>";

        if($nut_syslog_method == "file" || $nut_syslog_method == "both") {
            if(file_exists("/var/log/nut.log")) {
                $status[4] = "<span id='nut_log' class='tooltip-nut' data='NUT Service Logs'><a class='".($nut_footer_style == 0 ? "$green" : "$black")."' style='cursor:pointer;".($nut_footer_style == 0 ? "" : "color:inherit;")."' onclick=\"openTerminal('log','nut','nut.log')\"><i class='fa fa-book'></i></a></span>";
            }
        }

        ksort($status);

        $nutf_response["success"]["response"] = "<span style='margin:0 6px 0 6px'><span>".implode('</span><span style="margin:0 0 0 6px">', $status)."</span></span>";
    } else {
        $nutf_response["success"]["response"] =  "<span style='margin:0 0px 0 6px' id='nut_power' class='".($nut_footer_style == 0 ? "tooltip-nut" : "")."' data='$nut_name: UPS info not availabe, check your settings'><i class='fa fa-battery-empty'></i>&nbsp;n/a</span>";
    }
}
catch (\Throwable $t) {
    error_log($t);
    $nutf_response = [];
    $nutf_response["error"]["response"] = $t->getMessage();
}
catch (\Exception $e) {
    error_log($e);
    $nutf_response = [];
    $nutf_response["error"]["response"] = $e->getMessage();
}

echo(json_encode($nutf_response));
?>

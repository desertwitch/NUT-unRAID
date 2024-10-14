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

//  exit if NUT daemon isn't working
if (!$nut_running) {
  echo " ";
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
    $powerNominal = intval($nut_powerva);
    $realPowerNominal = intval($nut_powerw);

    if ($powerNominal > 0)
      $apparentPower = 0;

    if ($realPowerNominal > 0)
      $realPower = 0;
  }

  $ups_alarm = nut_array_key_exists_wildcard($ups_status, 'ups.alarm*');
  if (count($ups_alarm)) {
    $alarms = "";
    foreach ($ups_alarm as $al) {
      $alarms .= "<div><i class='fa fa-exclamation-circle orange-text'></i>&nbsp;".$ups_status[$al]."</div>";
    }
    $status[2] = "<span id='nut_alarm' class='tooltip-nut $orange' data=\"$alarms\"><i class='fa fa-bell faa-ring animated'></i></span>";
  }

  $battery_runtime = array_key_exists($nut_runtime, $ups_status) ? nut_format_time($ups_status[$nut_runtime],$nut_rtunit) : "n/a";
  $css_class = $online['severity'] > 0 ? $nut_msgSeverity[$online['severity']]['css_class'] : ($nut_footer_style == 1 ? $black : $green);
  $fa_icon = '';
  $statusTooltipData = '';
  $batteryText = $battery . "&thinsp;%";
  # if no battery info
  if ($battery === false) {
    $batteryText = " n/a";
    $fa_icon = "fa-battery-empty";
    $online['fulltext'][] = 'Battery info not available';
  # if ups.status contain CHRG
  } else if (is_array($online) && in_array('CHRG', $online['value'])) {
    $fa_icon = "fa-battery-charging";
  # if ups.status contain DISCHRG
  } else if (is_array($online) && in_array('DISCHRG', $online['value'])) {
    $fa_icon = "fa-battery-discharging";
    $online['fulltext'][] = "Est. " . $battery_runtime . " left";
  # if ups.status contain OB
  } else if (is_array($online) && in_array('OB', $online['value'])) {
  $fa_icon = "fa-battery-discharging";
  $online['fulltext'][] = "Est. " . $battery_runtime . " left";
  # other ups.status messages
  } else if (is_array($online) && $online['value']) {
    $fa_icon = "fa-battery-full";
    # blink battery icon if ups.status contain RB (Replace Battery)
    if (in_array('RB', $online['value']))
      $fa_icon .= ' fa-blink';
  # unknown status
  } else {
    $fa_icon = "fa-battery-empty";
    $online['fulltext'][] = 'Battery status unknown';
  }

  # enable tooltip on Default footer style
  if ($nut_footer_style == 0)
    $statusTooltipData = ' data="[' . $nut_name . '] ' . implode(' - ', $online['fulltext']) . '"';

  $status[0] = "<span id='" . ($nut_footer_style == 0 ? "nut_battery" : "") . "' class='tooltip-nut " . $css_class . "'" . $statusTooltipData . "><i class='fa " . $fa_icon . "' style='vertical-align: baseline;'></i>&thinsp;" . $batteryText . "</span>";

  # if no ups.load compute from ups.power(.nominal) or ups.realpower(.nominal)
  if ($load <= 0) {
    $load1 = 0;
    $load2 = 0;
    if ($apparentPower > 0 && $powerNominal > 0)
      $load1 = round($apparentPower / $powerNominal  * 100);
    if ($realPower > 0 && $realPowerNominal > 0)
      $load2 = round($realPower / $realPowerNominal  * 100);
    if($load1 > 1 && $load1 < 101)
      $load = $load1;
    if($load2 > 1 && $load2 < 101)
      $load = $load2;
  }

  # if no ups.power compute from load and ups.power.nominal
  if ($apparentPower <= 0)
   $apparentPower = $powerNominal > 0 && $load ? round($powerNominal * $load * 0.01) : 0;

  # if no ups.realpower compute from load and ups.realpower.nominal (in W)
  if ($realPower <= 0)
    $realPower = $realPowerNominal > 0 && $load ? round($realPowerNominal * $load * 0.01) : 0;

  $powerText = '';
  $powerTooltipData = '';
  # display load, real and apparent power
  if ($realPower > 0 && $apparentPower > 0) {
    $powerText = "{$realPower}&thinsp;W ({$apparentPower}&thinsp;VA)";
    $powerTooltipData = "Load: $load&thinsp;% - Real power: $realPower&thinsp;W - Apparent power: $apparentPower&thinsp;VA";
  # display load and real power
  } else if ($realPower > 0 && $load) {
    $powerText = "{$realPower}&thinsp;W";
    $powerTooltipData = "Load: $load&thinsp;% - Real power: $realPower&thinsp;W";
  # display load and apparent power
  } else if ($apparentPower > 0 && $load) {
    $powerText = "{$apparentPower}&thinsp;VA";
    $powerTooltipData = "Load: $load&thinsp;% - Apparent power: $apparentPower&thinsp;VA";
  # display load
  } else if ($load) {
    $powerText = "{$load}&thinsp;%";
    $powerTooltipData = "Load: $load&thinsp;%";
  }

  $powerTooltipData = " data='[{$nut_name}] " . $powerTooltipData . "'";

  # show connected clients in netserver mode
  if ($nut_manual == "disable" && $nut_mode == "netserver") {
    try {
      exec("/usr/bin/upsc -c ".escapeshellarg($nut_name)."@".escapeshellarg($nut_ip)." 2>/dev/null", $nutc_rows);
      if(!empty($nutc_rows)) {
        $nutc_count = count($nutc_rows);
        $status[3] = "<span id='nut_clients' class='".($nut_footer_style == 0 ? "tooltip-nut" : "")." ".($nut_footer_style == 0 ? "$green" : "$black")."' data=\"<b>NUT Connected Clients:</b><br>- ".implode("<br>- ",array_map('htmlspecialchars', $nutc_rows))."\"><i class='fa fa-user-circle'></i> $nutc_count</span>";
      }
    }
    catch (\Exception $e) {
      error_log($e);
      unset($status[3]);
    }
  }

  $status[1] = "<span id='".($nut_footer_style == 0 ? "nut_power" : "")."' class='".($nut_footer_style == 0 ? "tooltip-nut" : "")." " . ($load >= 90 ? $red : ($nut_footer_style == 1 ? $black : $green)) . "'" . $powerTooltipData . "><i class='fa fa-plug'></i>&thinsp;" . $powerText . "</span>";

  ksort($status);
  echo "<span style='margin:0 6px 0 12px'>".implode('</span><span style="margin:0 6px 0 6px">', $status)."</span>";
} else {
  echo "<span style='margin:0 6px 0 12px' id='nut_power' class='".($nut_footer_style == 0 ? "tooltip-nut" : "")."' data='$nut_name: UPS info not availabe, check your settings'><i class='fa fa-battery-empty'></i>&nbsp;n/a</span>";
}
?>

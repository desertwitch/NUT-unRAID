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
require_once '/usr/local/emhttp/plugins/nut/include/nut_config.php';

$red    = "class='red-text'";
$green  = "class='green-text'";
$orange = "class='orange-text'";
$status = array_fill(0,7,"<td>-</td>");
$all    = $_GET['all']=='true';
$result = [];
$rows = [];

if ($nut_running) {

  exec("/usr/bin/upsc ".escapeshellarg($nut_name)."@$nut_ip 2>/dev/null", $rows);
  
  if (isset($_GET['diagsave']) && $_GET['diagsave'] == "true") { 

    $diagarray = $rows;
  
    array_walk($diagarray, function(&$var) {
      if (preg_match('/^(device|ups)\.(serial|macaddr):/i', $var, $matches)) {
        $var = $matches[1] . '.' . $matches[2] . ': REMOVED';
      }
    });

    $diagstring = implode("\n",$diagarray);
    header('Content-Disposition: attachment; filename="nut-ups.dev"');
    header('Content-Type: text/plain');
    header('Content-Length: ' . strlen($diagstring));
    header('Connection: close');
    die($diagstring);

  }
  
  $upsStatus = nut_ups_status($rows);

  $runtime = 0;
  $realPower = 0;
  $realPowerNominal = 0;
  $apparentPower = 0;
  $powerNominal = 0;
  $load = 0;

  for ($i=0; $i<count($rows); $i++) {
    $row = array_map('trim', explode(':', $rows[$i], 2));
    $key = $row[0];
    $val = $row[1];
    switch ($key) {
    case 'ups.status':
      if ($upsStatus['fulltext'])
        $status[0] = '<td' . (isset($nut_msgSeverity[$upsStatus['severity']]) ? ' class="' . $nut_msgSeverity[$upsStatus['severity']]['css_class'] . '"' : '') . '>' . implode(' - ', $upsStatus['fulltext']) . '</td>';
      else
        $status[0] = '<td class="' . $nut_msgSeverity[1]['css_class'] . '">Refreshing...</td>';
      break;
    case 'battery.charge':
      $status[1] = strtok($val,' ')<=10 ? "<td $red>".intval($val). "&thinsp;%</td>" : "<td $green>".intval($val). "&thinsp;%</td>";
      break;
    case $nut_runtime:
      $runtime   = $nut_rtunit == "minutes" ? gmdate("H:i:s", round($val*60)) : gmdate("H:i:s", round($val));
      $status[2] = strtok(($nut_rtunit == "minutes" ? round($val) : round($val/60)),' ')<=5 && !in_array('ups.status: OL', $rows) ? "<td $red>$runtime</td>" : "<td $green>$runtime</td>";
      break;
    case 'ups.realpower':
      $realPower = strtok($val, ' ');
      break;
    case 'ups.realpower.nominal':
      $realPowerNominal = strtok($val,' ');
      break;
    case 'ups.power':
      $apparentPower = strtok($val, ' ');
      break;
    case 'ups.power.nominal':
      $powerNominal = strtok($val,' ');
      break;
    case 'ups.load':
      $load      = strtok($val,' ');
      break;
    }
    if ($all) {
      if ($i%2==0) $result[] = "<tr>";
      $result[]= "<td><strong>$key</strong></td><td>$val</td>";
      if ($i%2==1) $result[] = "</tr>";
    }
  }

  # if manual, overwrite values
  if ($nut_power == 'manual') {
    $powerNominal = intval($nut_powerva);
    $realPowerNominal = intval($nut_powerw);

    if ($powerNominal > 0)
      $apparentPower = 0;

    if ($realPowerNominal > 0)
      $realPower = 0;
  }

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

  if ($load > 0)
    $status[5] = $load>=90 ? "<td $red>".intval($load). "&thinsp;%</td>" : "<td $green>".intval($load). "&thinsp;%</td>";

  # if no ups.power compute from load and ups.power.nominal
  if ($apparentPower <= 0)
   $apparentPower = $powerNominal > 0 && $load ? round($powerNominal * $load * 0.01) : 0;

  # if no ups.realpower compute from load and ups.realpower.nominal (in W)
  if ($realPower <= 0)
    $realPower = $realPowerNominal > 0 && $load ? round($realPowerNominal * $load * 0.01) : 0;

  if ($powerNominal > 0 && $realPowerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$realPowerNominal&thinsp;W ($powerNominal&thinsp;VA)</td>";
  else if ($powerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$powerNominal&thinsp;VA</td>";
  else if ($realPowerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$realPowerNominal&thinsp;W</td>";

  # display apparent power and real power if exists
  if ($apparentPower > 0 && $realPower > 0)
    $status[4] = "<td " . ($realPower == 0 || $apparentPower == 0 ? $red : $green) . ">$realPower&thinsp;W ($apparentPower&thinsp;VA)</td>";
  else if ($apparentPower > 0)
    $status[4] = "<td " . ($apparentPower == 0 ? $red : $green) . ">$apparentPower&thinsp;VA</td>";
  else if ($realPower > 0)
    $status[4] = "<td " . ($realPower == 0 ? $red : $green) . ">$realPower&thinsp;W</td>";

  # compute power factor from ups.realpower.nominal and ups.power.nominal if available
  if ($realPowerNominal > 0 && $powerNominal > 0) {
    $status[6] = "<td $green>".round($realPowerNominal / $powerNominal, 2)."</td>";
  # or from real power and apparent power if available too (computed bellow).
  } else if ($realPower > 0 && $apparentPower > 0) {
    $status[6] = "<td $green>".round($realPower / $apparentPower, 2)."</td>";
  }
  if ($all && count($rows)%2==1) $result[] = "<td></td><td></td></tr>";
}
if ($all && !$rows) $result[] = "<tr><td colspan='4' style='text-align:center'>No information available</td></tr>";

echo "<tr>".implode('', $status)."</tr>";
if ($all) echo "\n".implode('', $result);
?>

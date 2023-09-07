<?PHP
/* Copyright 2017, Derek Macias.
 * Copyright 2005-2016, Lime Technology
 * Copyright 2015, Dan Landon.
 * Copyright 2015, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
require_once '/usr/local/emhttp/plugins/nut/include/nut_config.php';

$red    = "class='red-text'";
$green  = "class='green-text'";
$orange = "class='orange-text'";
$status = array_fill(0,7,"<td>-</td>");
$all    = $_GET['all']=='true';
$result = [];

if (file_exists('/var/run/nut/upsmon.pid')) {
  
  exec("/usr/bin/upsc ".escapeshellarg($nut_name)."@$nut_ip 2>/dev/null", $rows);
  
  if ($_GET['diagsave'] == "true") {

  $diagarray = $rows;
  
  array_walk($diagarray, function(&$var) {
    if (preg_match('/^(device|ups)\.(serial|macaddr):/i', $var, $matches)) {
      $var = $matches[1] . '.' . $matches[2] . ': REMOVED';
    }
  });

  $diagstring = implode("\n",$diagarray);
  header('Content-Disposition: attachment; filename="nut-diagnostics.dev"');
  header('Content-Type: text/plain');
  header('Content-Length: ' . strlen($diagstring));
  header('Connection: close');
  die($diagstring);

  }
  
  $upsStatus = nut_ups_status($rows);

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
      $runtime   = gmdate("H:i:s", $val);
      $status[2] = strtok($val/60,' ')<=5 && !in_array('ups.status: OL', $rows) ? "<td $red>$runtime</td>" : "<td $green>$runtime</td>";
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
      $status[5] = $load>=90 ? "<td $red>".intval($val). "&thinsp;%</td>" : "<td $green>".intval($val). "&thinsp;%</td>";
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
    if ($realPowerNominal >= 0)
      $realPower = -1;
      $apparentPower = -1;
  }

  # ups.power (in VA)
  $apparentPower = $apparentPower > 1 && $load ? $apparentPower : -1;
  # if no ups.power compute from load and ups.power.nominal
  if ($apparentPower < 0)
   $apparentPower = $powerNominal > 0 && $load ? round($powerNominal * $load * 0.01) : -1;

  # ups.realpower (in W)
  $realPower = $realPower > 1 && $load ? $realPower : -1;
  # if no ups.realpower compute from load and ups.realpower.nominal (in W)
  if ($realPower < 0)
    $realPower = $realPowerNominal > 0 && $load ? round($realPowerNominal * $load * 0.01) : -1;

  if ($powerNominal > 0 && $realPowerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$realPowerNominal&thinsp;W ($powerNominal&thinsp;VA)</td>";
  else if ($powerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$powerNominal&thinsp;VA</td>";
  else if ($realPowerNominal > 0)
    $status[3] = "<td " . ($load >= 90 ? $red : $green) . ">$realPowerNominal&thinsp;W</td>";

  # display apparent power and real power if exists
  if ($apparentPower >= 0 && $realPower >= 0)
    $status[4] = "<td " . ($realPower == 0 || $apparentPower == 0 ? $red : $green) . ">$realPower&thinsp;W ($apparentPower&thinsp;VA)</td>";
  else if ($apparentPower >= 0)
    $status[4] = "<td " . ($apparentPower == 0 ? $red : $green) . ">$apparentPower&thinsp;VA</td>";
  else if ($realPower >= 0)
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

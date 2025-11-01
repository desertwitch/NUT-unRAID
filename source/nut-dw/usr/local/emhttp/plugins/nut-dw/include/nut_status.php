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

$nuts_response = [];

try {
    $result = [];
    $rows = [];

    $red    = "class='red-text'";
    $green  = "class='green-text'";
    $orange = "class='orange-text'";
    $status = array_fill(0,7,"<td>-</td>");
    $all    = $_GET['all']=='true';

    if ($nut_running) {
        $rows = nut_status_rows($nut_name, $nut_ip);

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

        for ($z=0; $z<count($rows); $z++) {
            $arow = array_map('trim', explode(':', $rows[$z], 2));
            $aprop = $arow[0];
            if (stripos($aprop, "ups.alarm")!== false) {
                $nuts_response["success"]["alarms"][] = htmlspecialchars($arow[1]);
            }
        }

        $upsStatus = nut_ups_status($rows);

        $runtime = 0;
        $realPower = 0;
        $realPowerNominal = 0;
        $apparentPower = 0;
        $powerNominal = 0;
        $load = 0;

        $descriptorMapping = [];
        $descriptorFilePath = '/usr/share/nut/cmdvartab';
        try {
            if (file_exists($descriptorFilePath) && $descriptorFileHandle = fopen($descriptorFilePath, 'r')) {
                while (($descriptorFileLine = fgets($descriptorFileHandle)) !== false) {
                    if (preg_match('/^VARDESC\s+([a-zA-Z0-9_.]+)\s+"([^"]+)"$/', trim($descriptorFileLine), $descriptorRegexMatches)) {
                        $descriptorMapping[$descriptorRegexMatches[1]] = htmlspecialchars($descriptorRegexMatches[2]);
                    }
                }
                fclose($descriptorFileHandle);
            }
        } catch (\Throwable $t) {
            $descriptorMapping = [];
        } catch (\Exception $e) {
            $descriptorMapping = [];
        }

        for ($i=0; $i<count($rows); $i++) {
            $row = array_map('trim', explode(':', $rows[$i], 2));
            $key = $row[0];
            $val = $row[1];

            switch ($key) {
                case 'ups.status':
                    if ($upsStatus['fulltext']) {
                        $status[0] = '<td' . (isset($nut_msgSeverity[$upsStatus['severity']]) ? ' class="' . $nut_msgSeverity[$upsStatus['severity']]['css_class'] . '"' : '') . '>' . implode(' - ', $upsStatus['fulltext']) . '</td>';
                    } else {
                        $status[0] = '<td class="' . $nut_msgSeverity[1]['css_class'] . '">Refreshing...</td>';
                    }
                    break;
                case 'battery.charge':
                    $status[1] = "<td $green>".intval($val). "&thinsp;%</td>";
                    if (strtok($val,' ')<=50) {
                        $status[1] = "<td $orange>".intval($val). "&thinsp;%</td>";
                    }
                    if (strtok($val,' ')<=20) {
                        $status[1] = "<td $red>".intval($val). "&thinsp;%</td>";
                    }
                    break;
                case $nut_runtime:
                    if (!is_numeric($val)) break;
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

                if(isset($descriptorMapping[$key])) {
                    $result[]= "<td><span class='tooltip-nutvar' style='cursor:help;' title='$descriptorMapping[$key]'><strong>$key</strong></span></td><td>$val</td>";
                } else {
                    $result[]= "<td><strong>$key</strong></td><td>$val</td>";
                }

                if ($i%2==1) $result[] = "</tr>";
            }
        }

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

        # if no ups.load compute from ups.power(.nominal) or ups.realpower(.nominal)
        # also compute if calculation is otherwise forced by user setting
        if ($load <= 0 || $nut_loadcalc == "enable") {
            $loadW = 0; $loadVA = 0;
            if ($realPower > 0 && $realPowerNominal > 0) $loadW = round($realPower / $realPowerNominal  * 100);
            if ($apparentPower > 0 && $powerNominal > 0) $loadVA = round($apparentPower / $powerNominal  * 100);
            if ($nut_loadunit == "W" && $loadW > 1 && $loadW < 101) $load = $loadW;
            if ($nut_loadunit == "VA" && $loadVA > 1 && $loadVA < 101) $load = $loadVA;
        }

        if ($load > 0) {
            $status[5] = $load>=90 ? "<td $red>".intval($load). "&thinsp;%</td>" : "<td $green>".intval($load). "&thinsp;%</td>";
        }

        # if no ups.power compute from load and ups.power.nominal
        if ($apparentPower <= 0) $apparentPower = $powerNominal > 0 && $load ? round($powerNominal * $load * 0.01) : 0;

        # if no ups.realpower compute from load and ups.realpower.nominal (in W)
        if ($realPower <= 0) $realPower = $realPowerNominal > 0 && $load ? round($realPowerNominal * $load * 0.01) : 0;

        if ($powerNominal > 0 && $realPowerNominal > 0) {
            $status[3] = "<td $green>$realPowerNominal&thinsp;W ($powerNominal&thinsp;VA)</td>";
        } elseif ($powerNominal > 0) {
            $status[3] = "<td $green>$powerNominal&thinsp;VA</td>";
        } elseif ($realPowerNominal > 0) {
            $status[3] = "<td $green>$realPowerNominal&thinsp;W</td>";
        }

        # display apparent power and real power if exists
        if ($apparentPower > 0 && $realPower > 0) {
            $status[4] = "<td " . ($load >= 90 ? $red : $green) . ">$realPower&thinsp;W ($apparentPower&thinsp;VA)</td>";
        } elseif ($apparentPower > 0) {
            $status[4] = "<td " . ($load >= 90 ? $red : $green) . ">$apparentPower&thinsp;VA</td>";
        } elseif ($realPower > 0) {
            $status[4] = "<td " . ($load >= 90 ? $red : $green) . ">$realPower&thinsp;W</td>";
        }

        if ($realPower > 0 && $apparentPower > 0) {
            # compute output power factor from real power and apparent power if available
            $status[6] = "<td $green>".round($realPower / $apparentPower, 2)."</td>";
        }
        elseif ($realPowerNominal > 0 && $powerNominal > 0) {
            # or present nominal power factor from ups.realpower.nominal and ups.power.nominal if available
            $status[6] = "<td $green>".round($realPowerNominal / $powerNominal, 2)."</td>";
        }

        if ($all && count($rows)%2==1) $result[] = "<td></td><td></td></tr>";
    }
    if ($all && !$rows) $result[] = "<tr><td colspan='4' style='text-align:center'>No information available</td></tr>";

    if($all) {
        $nuts_response["success"]["response"] = "<tr>".implode('', $status)."</tr>";
        $nuts_response["success"]["allvars"] = implode('', $result);
    } else {
        $nuts_response["success"]["response"] = "<tr>".implode('', $status)."</tr>";
    }
}
catch (\Throwable $t) {
    error_log($t);
    $nuts_response = [];
    $nuts_response["error"]["response"] = $t->getMessage();
}
catch (\Exception $e) {
    error_log($e);
    $nuts_response = [];
    $nuts_response["error"]["response"] = $e->getMessage();
}

echo(json_encode($nuts_response));
?>

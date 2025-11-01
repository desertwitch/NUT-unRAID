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

function nut_status_rows($name, $ip) {
    $rows = [];
    $status = 1;

    $cmd = '/usr/bin/upsc '
        . escapeshellarg($name) . '@' . escapeshellarg($ip) . ' 2>/dev/null';

    exec($cmd, $rows, $status);

    if ($status === 0 && !empty($rows)) {
        return $rows;
    }

    // Fallback for broken NUT protocol implementations
    // Try to get a number of known important variables one by one...
    $vars = [
        'battery.charge',
        'battery.low',
        'battery.runtime',
        'battery.voltage',
        'input.frequency',
        'input.transfer.high',
        'input.transfer.low',
        'input.voltage.nominal',
        'input.voltage',
        'output.current',
        'output.frequency',
        'output.power.nominal',
        'output.power',
        'output.voltage',
        'ups.id',
        'ups.load',
        'ups.mfr',
        'ups.model',
        'ups.serial',
        'ups.status',
        'ups.test.date',
        'ups.test.interval',
        'ups.test.result',
        'ups.type',
    ];

    $rows = [];
    foreach ($vars as $var) {
        $out = [];
        $code = 1;

        $cmd = '/usr/bin/upsc '
             . escapeshellarg($name) . '@' . escapeshellarg($ip) . ' '
             . escapeshellarg($var) . ' 2>/dev/null';

        exec($cmd, $out, $code);

        if ($code === 0 && isset($out[0]) && $out[0] !== '') {
            $rows[] = $var . ': ' . $out[0];
        }
    }

    if(!empty($rows)) {
        $rows[] = "x.plugin.varfetch: fallback";
    }

    return $rows;
}

/* get options for battery level */
function nut_get_battery_options($selected=20){
    $range = [1,99];
    rsort($range);
    $options = "";
    foreach(range($range[0], $range[1], 1) as $level){
        $options .= "<option value='$level'";

        // set saved option as selected
        if (intval($selected) === $level) {
            $options .= " selected";
        }

        $options .= ">$level</option>";
    }
    return $options;
}

/* get options for time intervals */
function nut_get_minute_options($time){
    $options = '';
        for($i = 1; $i <= 180; $i++){
            $options .= '<option value="'.($i*60).'"';

            if(intval($time) === ($i*60)) {
                $options .= ' selected';
            }

            $options .= '>'.$i.'</option>';
        }
    return $options;
}

function nut_ups_status($rows, $valueOnly = false)
{
    global $nut_states;

    $severity = 0;
    $status_values = [];
    $status_fulltext = [];

    array_walk($rows, function($row) use (&$severity, &$status_fulltext, &$status_values, $nut_states, $valueOnly) {
        if ($valueOnly) {
            # if only ups.status value as param
            $status_values = explode(' ', $row);
        }
        elseif (preg_match('/^ups.status:\s*([^$]+)/i', $row, $matches)) {
            # if status array as param, find ups.status
            $status_values = explode(' ', $matches[1]);
        } else {
            # skip everything else
            return;
        }

        # if debug constant defined, overwrite ups.status values
        if (defined('NUT_STATUS_DEBUG')) {
            $status_values = explode(' ', NUT_STATUS_DEBUG);
        }

        # replace ups.status flags with full text message.
        $status_fulltext = array_map(function($var) use (&$severity, $nut_states) {
            if (isset($nut_states[$var]) && $nut_states[$var]) {
                # keep the highest severity message level
                $severity = max($severity, $nut_states[$var]['severity']);
                return $nut_states[$var]['msg'];
            } else {
                # if unknown status flag, return it
                return $var;
            }
        }, $status_values);
    });

    # return highest severity message level, array of status flags and array of full text status message
    return ['severity' => $severity, 'value' => $status_values, 'fulltext' => $status_fulltext];
}

function nut_download_url($url, $conn_timeout = 15, $timeout = 45) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conn_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, "");
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $out = curl_exec($ch) ?: false;
        curl_close($ch);
        return $out;
    } catch (\Throwable $t) { // For PHP 7
        return false;
    } catch (\Exception $e) { // For PHP 5
        return false;
    }
}

function nut_get_dev_message() {
    try {
        $dev_message_url = "https://raw.githubusercontent.com/desertwitch/NUT-unRAID/master/plugin/developer_message";
        $raw_dev_message = nut_download_url($dev_message_url, 30, 45);
        if($raw_dev_message && strpos($raw_dev_message, "NODISPLAY") === false) {
            return $raw_dev_message;
        } else {
            return false;
        }
    } catch (\Throwable $t) { // For PHP 7
        return false;
    } catch (\Exception $e) { // For PHP 5
        return false;
    }
}

function nut_tailFile($filePath, $lines = 90) {
    try {
        if (!file_exists($filePath)) {
            throw new Exception("... requested syslog file is either empty or does not exist (yet)");
        }

        $f = fopen($filePath, "r");
        if (!$f) {
            throw new Exception("... requested syslog file exists but was not accessible");
        }

        $buffer = 4096;
        $lineCount = 0;
        $position = -1;
        $text = '';

        fseek($f, 0, SEEK_END);

        while ($lineCount < $lines) {
            $position -= $buffer;
            if ($position < -ftell($f)) {
                $position = -ftell($f);
            }

            fseek($f, $position, SEEK_END);
            $chunk = fread($f, $buffer);
            $text = $chunk . $text;
            $lineCount = substr_count($text, "\n");

            if ($position == -ftell($f)) {
                break;
            }
        }

        fclose($f);

        $allLines = explode("\n", str_replace("\r\n", "\n", $text));
        $sanitizedLines = array_map('htmlspecialchars', array_slice($allLines, -$lines));
        return implode("\n", $sanitizedLines);
    } catch (\Throwable $t) {
        return $t->getMessage();
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

?>

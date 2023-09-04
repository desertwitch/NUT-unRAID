<?
/* get options for battery level */
function get_battery_options($selected=20){
    $range = [1,99];
    rsort($range);
    $options = "";
    foreach(range($range[0], $range[1], 1) as $level){
        $options .= "<option value='$level'";

        // set saved option as selected
        if (intval($selected) === $level)
            $options .= " selected";

        $options .= ">$level</option>";
    }
    return $options;
}

/* get options for time intervals */
function get_minute_options($time){
    $options = '';
        for($i = 1; $i <= 60; $i++){
            $options .= '<option value="'.($i*60).'"';
            if(intval($time) === ($i*60))
                $options .= ' selected';

            $options .= '>'.$i.'</option>';
        }
    return $options;
}

function write_php_ini($array, $file)
{
    $res = array();
    foreach($array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey=".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key=".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{
    if ($fp = fopen($fileName, 'w'))
    {
        $startTime = microtime();
        do
        {            $canWrite = flock($fp, LOCK_EX);
           // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
           if(!$canWrite) usleep(round(rand(0, 100)*1000));
        } while ((!$canWrite)and((microtime()-$startTime) < 1000));

        //file was locked so now we can store information
        if ($canWrite)
        {            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}

function nut_ups_status($rows, $valueOnly = false)
{
    global $nut_states;

    $severity = 0;
    $status_values = [];
    $status_fulltext = [];
    array_walk($rows, function($row) use (&$severity, &$status_fulltext, &$status_values, $nut_states, $valueOnly) {
        # if only ups.status value as param
        if ($valueOnly)
            $status_values = explode(' ', $row);
        # if status array as param, find ups.status
        else if (preg_match('/^ups.status:\s*([^$]+)/i', $row, $matches))
            $status_values = explode(' ', $matches[1]);
        # skip everything else
        else
            return;

        # if debug constant defined, overwrite ups.status values
        if (defined('NUT_STATUS_DEBUG'))
            $status_values = explode(' ', NUT_STATUS_DEBUG);

        # replace ups.status flags with full text message.
        $status_fulltext = array_map(function($var) use (&$severity, $nut_states) {
            if (isset($nut_states[$var]) && $nut_states[$var]) {
                # keep the highest severity message level
                $severity = max($severity, $nut_states[$var]['severity']);
                return $nut_states[$var]['msg'];
            # if unknown status flag, return it
            } else {
                return $var;
            }
        }, $status_values);
    });

    # return highest severity message level, array of status flags and array of full text status message
    return ['severity' => $severity, 'value' => $status_values, 'fulltext' => $status_fulltext];
}

?>
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

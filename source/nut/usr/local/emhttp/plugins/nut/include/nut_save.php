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
$base     = '/etc/nut/';
$plgpath  = '/boot/config/plugins/nut/ups/';
$editfile = realpath($_POST['editfile']);
$plgfile  = $plgpath.basename($editfile);

if(!strpos($editfile, $base) && file_exists($editfile) && array_key_exists('editdata', $_POST)){
    // remove carriage returns
    $editdata = str_replace("\r", '', $_POST['editdata']);

    // create directory on flash drive if missing (shouldn't happen)
    if(! is_dir($plgpath)){
        mkdir($plgpath);
    }

    // get previous config file contents and save them
    if(file_exists($plgfile)){
        $plgfile_old = file_get_contents($plgfile);
        file_put_contents($plgfile.'.old', $plgfile_old);
    }

    // save conf file to flash drive regardless of mode
    file_put_contents($plgfile, $editdata);

    // save conf file to local system as well
    file_put_contents($editfile, $editdata);

    // save file contents
    $return_var = file_put_contents($editfile, $editdata);
}else{
    $return_var = false;
}

if($return_var)
    $return = ['success' => true, 'saved' => $editfile];
else
    $return = ['error' => $editfile];

echo json_encode($return);
?>

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
require_once __DIR__ . '/nut_paths.php';

$plgpath  = '/boot/config/plugins/nut-dw/ups/';
$editfile = nut_resolve_config_file($_POST['editfile'] ?? null);
$return_var = false;

if ($editfile !== false && array_key_exists('editdata', $_POST)) {
    // remove carriage returns
    $editdata = str_replace("\r", '', $_POST['editdata']);
    $plgfile = $plgpath . basename($editfile);

    // create directory on flash drive if missing (shouldn't happen)
    if (!is_dir($plgpath)) {
        mkdir($plgpath, 0755, true);
    }

    // save conf file to flash drive regardless of mode
    $flash_saved = file_put_contents($plgfile, $editdata);

    // save conf file to local system as well
    $config_saved = file_put_contents($editfile, $editdata);
    $return_var = $flash_saved !== false && $config_saved !== false;
}

if($return_var !== false) {
    $return = ['success' => true, 'saved' => $editfile];
} else {
    $return = ['error' => $editfile ?: 'Invalid File'];
}

echo json_encode($return);
?>

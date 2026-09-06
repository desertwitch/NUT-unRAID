<?php

require_once __DIR__ . '/../source/nut-dw/usr/local/emhttp/plugins/nut-dw/include/nut_paths.php';

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nut-paths-' . bin2hex(random_bytes(4));
$base = $root . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'nut';
$outside = $root . DIRECTORY_SEPARATOR . 'outside.conf';

mkdir($base, 0755, true);
file_put_contents($base . DIRECTORY_SEPARATOR . 'ups.conf', "driver = usbhid-ups\n");
file_put_contents($base . DIRECTORY_SEPARATOR . 'not-allowed.txt', "no\n");
file_put_contents($outside, "outside\n");

try {
    $valid = nut_resolve_config_file($base . DIRECTORY_SEPARATOR . 'ups.conf', $base);
    if ($valid === false) {
        throw new RuntimeException('A valid NUT configuration file was rejected.');
    }

    if (nut_resolve_config_file($outside, $base) !== false) {
        throw new RuntimeException('A path outside the NUT configuration directory was accepted.');
    }

    if (nut_resolve_config_file($base . DIRECTORY_SEPARATOR . 'not-allowed.txt', $base) !== false) {
        throw new RuntimeException('A disallowed file extension was accepted.');
    }

    $link = $base . DIRECTORY_SEPARATOR . 'escaped.conf';
    if (@symlink($outside, $link) && nut_resolve_config_file($link, $base) !== false) {
        throw new RuntimeException('A symlink escaping the NUT configuration directory was accepted.');
    }

    echo "NUT configuration path confinement passed.\n";
} finally {
    @unlink($base . DIRECTORY_SEPARATOR . 'escaped.conf');
    @unlink($base . DIRECTORY_SEPARATOR . 'ups.conf');
    @unlink($base . DIRECTORY_SEPARATOR . 'not-allowed.txt');
    @unlink($outside);
    @rmdir($base);
    @rmdir(dirname($base));
    @rmdir($root);
}

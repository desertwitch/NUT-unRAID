<?php

require_once __DIR__ . '/../source/nut-dw/usr/local/emhttp/plugins/nut-dw/include/nut_helpers.php';

$input = 'Update <em>available</em><script>alert(1)</script> &amp; ready.';
$expected = 'Update availablealert(1) & ready.';

if (nut_dev_message_text($input) !== $expected) {
    fwrite(STDERR, "Developer message was not converted to plain text.\n");
    exit(1);
}

echo "Developer message plain-text rendering passed.\n";

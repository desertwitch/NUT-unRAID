<?php

require_once __DIR__ . '/../source/nut-dw/usr/local/emhttp/plugins/nut-dw/include/nut_helpers.php';

$input = '"><img src=x onerror=alert(1)>&';
$expected = '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;&amp;';

if (nut_html($input) !== $expected) {
    fwrite(STDERR, "UPS output was not escaped safely.\n");
    exit(1);
}

$afterFooterInsertion = html_entity_decode(
    nut_tooltip_html($input),
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);

if ($afterFooterInsertion !== $expected) {
    fwrite(STDERR, "UPS tooltip output did not remain escaped after footer insertion.\n");
    exit(1);
}

echo "UPS output escaping passed.\n";

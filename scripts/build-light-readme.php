<?php

/**
 * Generates README-light.md from README.md.
 *
 *     php scripts/build-light-readme.php
 *
 * GitHub has no theme toggle, so the toggle is a pair of pages that link to each other.
 * They must stay identical apart from the screenshot paths and that one link, which is
 * why the light page is generated rather than maintained by hand: edit README.md, run
 * this, commit both.
 *
 * Written in PHP rather than Node so the repository keeps its promise of no build step
 * and no toolchain beyond PHP itself.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$src = $root . '/README.md';
$out = $root . '/README-light.md';

$replacements = [
    'docs/screenshots/dark/' => 'docs/screenshots/light/',
    'docs/screenshots/responsive/dark/' => 'docs/screenshots/responsive/light/',
    '<p><b>Dark mode</b> · <a href="./README-light.md">View this page in light mode</a></p>'
        => '<p><b>Light mode</b> · <a href="./README.md">View this page in dark mode</a></p>',
    'This page shows **dark mode**; the same gallery in light mode is at **[README-light.md](./README-light.md)**.'
        => 'This page shows **light mode**; the same gallery in dark mode is at **[README.md](./README.md)**.',
];

$text = file_get_contents($src);
if ($text === false) {
    fwrite(STDERR, "Cannot read $src\n");
    exit(1);
}

foreach ($replacements as $from => $to) {
    // Fail loudly rather than silently emitting a page that still points at the dark
    // screenshots, which is the one failure nobody would notice in review.
    if (!str_contains($text, $from)) {
        fwrite(STDERR, "README.md is missing the expected marker: $from\n");
        exit(1);
    }
    $text = str_replace($from, $to, $text);
}

$header = "<!-- Generated from README.md by scripts/build-light-readme.php. Do not edit by hand. -->\n\n";
file_put_contents($out, $header . $text);

echo "Wrote README-light.md from README.md\n";

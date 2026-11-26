<?php

// Git does not keep empty directories, so create the writable ones Laravel
// needs before Composer dumps the autoloader (package:discover writes to
// bootstrap/cache, Blade compiles into storage/framework/views).

$base = dirname(__DIR__);

$directories = [
    'bootstrap/cache',
    'storage/app',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
];

foreach ($directories as $directory) {
    $path = $base.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
        fwrite(STDERR, "Could not create {$directory}\n");
        exit(1);
    }
}

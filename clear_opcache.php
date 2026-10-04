<?php
// One-off: clear PHP's opcode cache so the web server re-reads source files from disk instead of
// serving a compiled-before-the-last-git-pull version. Delete this file right after using it once.
if (function_exists('opcache_reset')) {
    $ok = opcache_reset();
    echo $ok ? 'opcache_reset: OK' : 'opcache_reset: FAILED (returned false)';
} else {
    echo 'opcache_reset: function does not exist (OPcache not enabled for this SAPI)';
}

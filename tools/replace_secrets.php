<?php
// Replace known secret literals with placeholder in repository files.
// This script is intended to be called by git filter-branch --tree-filter

$secrets = [
    'REMOVED_BY_GIT_HISTORY_REWRITE',
    'REMOVED_BY_GIT_HISTORY_REWRITE',
    'REMOVED_BY_GIT_HISTORY_REWRITE',
    'REMOVED_BY_GIT_HISTORY_REWRITE',
    // add other known secrets (literal substrings) here if needed
];

$placeholder = 'REMOVED_BY_GIT_HISTORY_REWRITE';

function shouldProcessFile($path) {
    // skip .git and binary files
    $lower = strtolower($path);
    if (strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) return false;
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $skipExt = ['png','jpg','jpeg','gif','exe','dll','so','zip','tar','gz','rar','7z','pdf'];
    if (in_array($ext, $skipExt, true)) return false;
    return is_file($path) && is_readable($path) && is_writable($path);
}

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(getcwd()));
foreach ($rii as $file) {
    $path = $file->getPathname();
    if (!shouldProcessFile($path)) continue;
    $content = @file_get_contents($path);
    if ($content === false) continue;
    $replaced = $content;
    foreach ($secrets as $s) {
        if ($s === '') continue;
        $replaced = str_replace($s, $placeholder, $replaced);
    }
    if ($replaced !== $content) {
        @file_put_contents($path, $replaced);
    }
}

// Also remove any .env files present in the checked-out tree
@unlink(getcwd() . DIRECTORY_SEPARATOR . '.env');

?>

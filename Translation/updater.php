<?php
if (php_sapi_name() !== "cli") {
    die("Please use command line: php updater.php");
}

/// scan json files
chdir(__DIR__);
$files = [];
foreach (scandir(__DIR__, SCANDIR_SORT_ASCENDING) as $filename) {
    if (is_file($filename) && substr($filename, -5) === '.json') {
        $files[] = $filename;
    }
}

/// download json from facturascripts.com
foreach ($files as $filename) {
    $url = "https://facturascripts.com/EditLanguage?action=json&idproject=93&code=";
    $json = file_get_contents($url . substr($filename, 0, -5));
    if (empty($json) || strlen($json) < 10) {
        unlink($filename);
        echo "Remove " . $filename . "\n";
        continue;
    }

    echo "Download " . $filename . "\n";
    file_put_contents($filename, $json);
}
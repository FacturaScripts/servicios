<?php
if (php_sapi_name() !== "cli") {
    die("Please use command line: php updater.php");
}

/// scan json files
chdir(__DIR__);
$files = [];
$langs = 'ca_ES,de_DE,en_EN,es_AR,es_CL,es_CO,es_CR,es_DO,es_EC,es_ES,es_GT,es_MX,es_PE,es_UY,eu_ES,fr_FR,gl_ES,it_IT,pt_PT,va_ES';
foreach (explode(',', $langs) as $lang) {
    $files[] = $lang . '.json';
}
foreach (scandir(__DIR__, SCANDIR_SORT_ASCENDING) as $filename) {
    if (is_file($filename) && substr($filename, -5) === '.json' && false === in_array($filename, $files)) {
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
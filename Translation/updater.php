<?php
if (PHP_SAPI !== 'cli') {
    die("Please use command line: php updater.php");
}

chdir(__DIR__);
$projectId = 93;

// Languages available on facturascripts.com, or this list if they cannot be downloaded.
$languages = ["ca_ES","cs_CZ","de_DE","en_EN","es_AR","es_CL","es_CO","es_CR","es_DO","es_EC","es_ES","es_GT","es_MX","es_PA","es_PE","es_UY","eu_ES","fr_FR","gl_ES","it_IT","nl_NL","pl_PL","pt_BR","pt_PT","tr_TR","va_ES"];
[$listContent, $listError] = downloadTranslations('https://facturascripts.com/traducciones?action=json-languages');
$remoteLanguages = $listError === null ? json_decode($listContent, true) : null;
if (is_array($remoteLanguages) && $remoteLanguages !== [] && false === array_is_list($remoteLanguages)) {
    $languages = array_values(array_filter(array_keys($remoteLanguages), function ($code) {
        return is_string($code) && preg_match('/^[a-zA-Z0-9_-]{1,8}$/D', $code) === 1;
    }));
} else {
    fwrite(STDERR, "Error downloading the language list, using the built-in list\n");
}

// Include supported languages and any translation files already present in the plugin.
$files = [];
foreach ($languages as $language) {
    $files[] = $language . '.json';
}
foreach (scandir(__DIR__, SCANDIR_SORT_ASCENDING) as $filename) {
    if (is_file($filename)
        && preg_match('/^[a-zA-Z0-9_-]{1,8}\.json$/D', $filename)
        && false === in_array($filename, $files, true)) {
        $files[] = $filename;
    }
}

$errors = 0;
foreach ($files as $index => $filename) {
    if ($index > 0) {
        sleep(1);
    }

    $language = pathinfo($filename, PATHINFO_FILENAME);
    $url = 'https://facturascripts.com/EditLanguage?' . http_build_query([
        'action' => 'json',
        'idproject' => $projectId,
        'code' => $language,
    ]);

    [$newContent, $downloadError] = downloadTranslations($url);
    if ($downloadError !== null) {
        fwrite(STDERR, "Error {$filename}: {$downloadError}\n");
        $errors++;
        continue;
    }

    try {
        $translations = json_decode($newContent, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fwrite(STDERR, "Error {$filename}: invalid JSON (" . $exception->getMessage() . ")\n");
        $errors++;
        continue;
    }

    if (false === is_array($translations)
        || ($translations !== [] && array_is_list($translations))) {
        fwrite(STDERR, "Error {$filename}: the response is not a JSON object\n");
        $errors++;
        continue;
    }

    foreach ($translations as $name => $translation) {
        if (false === is_string($name)
            || preg_match('/^[a-zA-Z0-9_+\-]{2,100}$/D', $name) !== 1
            || false === is_string($translation)) {
            fwrite(STDERR, "Error {$filename}: invalid translation data\n");
            $errors++;
            continue 2;
        }
    }

    // Only remove a stale file when the server returned valid, empty JSON.
    if ($translations === []) {
        $fileExists = is_file($filename);
        if ($fileExists && false === unlink($filename)) {
            fwrite(STDERR, "Error {$filename}: could not remove the file\n");
            $errors++;
            continue;
        }

        echo $fileExists ? "Remove {$filename}\n" : "Empty {$filename}\n";
        continue;
    }

    $oldContent = is_file($filename) ? file_get_contents($filename) : false;
    if ($newContent === $oldContent) {
        echo "Skip {$filename}\n";
        continue;
    }

    if (false === saveTranslations($filename, $newContent)) {
        fwrite(STDERR, "Error {$filename}: could not write the file\n");
        $errors++;
        continue;
    }

    echo "Download {$filename}\n";
}

exit($errors > 0 ? 1 : 0);

function downloadTranslations(string $url): array
{
    $curl = curl_init($url);
    if ($curl === false) {
        return [null, 'could not initialize cURL'];
    }

    curl_setopt_array($curl, [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'FacturaScripts translation updater',
    ]);

    $content = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($content === false) {
        return [null, $error ?: 'download failed'];
    }
    if ($statusCode !== 200) {
        return [null, "HTTP {$statusCode}"];
    }

    return [$content, null];
}

function saveTranslations(string $filename, string $content): bool
{
    $tempFile = tempnam(__DIR__, '.translation-');
    if ($tempFile === false) {
        return false;
    }

    if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
        unlink($tempFile);
        return false;
    }

    chmod($tempFile, is_file($filename) ? (fileperms($filename) & 0777) : 0644);
    if (false === rename($tempFile, $filename)) {
        unlink($tempFile);
        return false;
    }

    return true;
}

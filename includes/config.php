<?php
/** Local configuration loader. The installer creates the ignored .env file. */
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) { continue; }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name); $value = trim($value);
        if (($value[0] ?? '') === '"' && str_ends_with($value, '"')) { $value = substr($value, 1, -1); }
        if ($name !== '' && getenv($name) === false) { putenv($name . '=' . $value); $_ENV[$name] = $value; }
    }
}
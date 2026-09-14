<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/Database.php';

function migrationStatements(string $contents): array
{
    $contents = preg_replace('/^\s*--.*$/m', '', $contents) ?? '';
    return array_values(array_filter(array_map('trim', explode(';', $contents))));
}

$messages = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $database->fetchColumn('SELECT 1 FROM users LIMIT 1');
        $database->execute(
            'CREATE TABLE IF NOT EXISTS phpretro_schema_migrations (
                filename VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        foreach (glob(__DIR__ . '/../migrations/*.sql') ?: [] as $migration) {
            $filename = basename($migration);
            if ($database->fetchColumn('SELECT filename FROM phpretro_schema_migrations WHERE filename = ?', [$filename])) {
                $messages[] = $filename . ' already applied.';
                continue;
            }

            foreach (migrationStatements((string) file_get_contents($migration)) as $statement) {
                $database->execute($statement);
            }
            $database->execute(
                'INSERT INTO phpretro_schema_migrations (filename, applied_at) VALUES (?, ?)',
                [$filename, time()]
            );
            $messages[] = $filename . ' applied.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>PHPRetro Installer</title>
<link rel="stylesheet" href="../web-gallery/v2/styles/style.css"><link rel="stylesheet" href="../web-gallery/v2/styles/process.css"><link rel="stylesheet" href="./images/style.css">
<style>body{margin:0;background:#d9e7ef;color:#263746;font:14px Arial,sans-serif}.card{width:760px;margin:56px auto;padding:26px;background:#fff;border:1px solid #9ab5c5;border-radius:7px;box-shadow:0 4px 12px #7893a544}h1{margin-top:0;color:#174d72}.notice{padding:12px;margin:16px 0;border-radius:5px;background:#e8f5fb}.error{background:#fdeaea;color:#8c2525}button{border:0;border-radius:5px;padding:10px 18px;background:#2787bd;color:#fff;font-weight:bold;cursor:pointer}code{background:#edf2f6;padding:2px 4px;border-radius:3px}</style>
</head>
<body>
<main class="card process-template-box">
<div id="header"><h1>PHPRetro Installer</h1><ul class="stats"><li>1/1 &nbsp; Polaris setup</li></ul></div>
<h2>PHPRetro Polaris installer</h2>
<p>This setup keeps Polaris intact. It requires a configured Polaris database with a <code>users</code> table and applies only PHPRetro's own migrations.</p>
<?php if ($error !== null): ?>
<p class="notice error"><strong>Installation failed:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php foreach ($messages as $message): ?>
<p class="notice"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
<form method="post"><button type="submit">Apply PHPRetro migrations</button></form>
</main>
</body>
</html>

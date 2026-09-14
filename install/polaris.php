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
<head><meta charset="utf-8"><title>PHPRetro Polaris installer</title></head>
<body>
<h1>PHPRetro Polaris installer</h1>
<p>This installer does not create, alter, or delete Polaris tables. It requires a configured Polaris database with a <code>users</code> table and applies only the repository's <code>migrations/*.sql</code> files.</p>
<?php if ($error !== null): ?>
<p><strong>Installation failed:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php foreach ($messages as $message): ?>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
<form method="post"><button type="submit">Apply PHPRetro migrations</button></form>
</body>
</html>

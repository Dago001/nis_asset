<?php
/**
 * Simple forward-only migration runner.
 *
 *   php scripts/migrate.php
 *
 * Applies every *.sql file in database/migrations/ that has not been recorded
 * in the `schema_migrations` table yet, in filename order.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/../config/init.php';

$pdo = Database::getInstance();

$pdo->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `filename`   VARCHAR(191) PRIMARY KEY,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$applied = $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN) ?: [];
$dir = __DIR__ . '/../database/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying {$name} ... ";
    $sql = file_get_contents($file);

    try {
        // Strip full-line "-- comment" lines, then split on ";" at line end.
        $clean = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (preg_split('/;\s*[\r\n]+/', $clean) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            $pdo->exec($stmt);
        }
        $ins = $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
        $ins->execute([$name]);
        echo "OK\n";
        $ran++;
    } catch (Throwable $e) {
        echo "FAILED\n  " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo $ran === 0 ? "Nothing to do — database is up to date.\n" : "Done. {$ran} migration(s) applied.\n";

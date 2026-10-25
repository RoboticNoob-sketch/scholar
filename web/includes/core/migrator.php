<?php

declare(strict_types=1);

/**
 * Applies pending database/migrations/*.sql files in filename order.
 * Applied files are recorded in schema_migrations. Migrations must be repeatable (guarded DDL).
 */
function run_migrations(PDO $pdo, array $config): void
{
    if (($config['auto_migrate'] ?? true) === false) {
        return;
    }

    $dir = __DIR__ . '/../../../database/migrations';
    $files = glob($dir . '/*.sql') ?: [];
    if ($files === []) {
        return;
    }
    sort($files, SORT_STRING);

    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(190) NOT NULL PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $applied = array_flip($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN));
        $pending = array_filter($files, static fn (string $f): bool => !isset($applied[basename($f)]));
        if ($pending === []) {
            return;
        }

        $lockStmt = $pdo->query("SELECT GET_LOCK('scholarly_migrations', 10)");
        $lock = $lockStmt->fetchColumn();
        $lockStmt->closeCursor();
        if ((int) $lock !== 1) {
            return;
        }

        try {
            $applied = array_flip($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN));
            foreach ($pending as $file) {
                $name = basename($file);
                if (isset($applied[$name])) {
                    continue;
                }

                $sql = (string) file_get_contents($file);
                if (str_contains($sql, '{{SYSTEM_ADMIN_PASSWORD_HASH}}')) {
                    $hash = (string) ($config['system_admin_password_hash'] ?? '');
                    if ($hash === '') {
                        error_log("Migration $name skipped: system_admin_password_hash is not configured.");
                        continue;
                    }
                    $sql = str_replace('{{SYSTEM_ADMIN_PASSWORD_HASH}}', str_replace("'", "''", $hash), $sql);
                }

                foreach (split_sql_statements($sql) as $statement) {
                    $pdo->query($statement)->closeCursor();
                }
                $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)')->execute([$name]);
            }
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('scholarly_migrations')")->closeCursor();
        }
    } catch (Throwable $e) {
        error_log('Migration failed: ' . $e->getMessage());
    }
}

/**
 * @return list<string>
 */
function split_sql_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $quote = null;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($quote !== null) {
            $current .= $ch;
            if ($ch === '\\' && $quote !== '`') {
                $current .= $next;
                $i++;
            } elseif ($ch === $quote) {
                if ($next === $quote) {
                    $current .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $current .= $ch;
        } elseif ($ch === '#' || ($ch === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' '))) {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            $current .= "\n";
        } elseif ($ch === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
        } elseif ($ch === ';') {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
        } else {
            $current .= $ch;
        }
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return $statements;
}

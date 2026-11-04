<?php
/**
 * One-off recovery tool: put the exam tables back after course_exams was deleted.
 *
 * Why this exists as a server script rather than a local import: the dump is tens of
 * megabytes, and shared hosting caps phpMyAdmin uploads well below that. The hosting
 * backup is already sitting on the server, so the work is done where the file is and
 * nothing large has to travel.
 *
 * WHAT WAS LOST
 * -------------
 * course_exams carries two ON DELETE CASCADE children, so deleting it took with it:
 *
 *   course_exams
 *   |-- exam_attempts            -> exam_attempt_answers
 *   `-- questions                -> questions_translations, mcq_options (-> its
 *                                   translations), true_false_answers,
 *                                   written_model_answers (-> its translations)
 *
 * Two tables hold exam_id with NO constraint - course_exams_translations and
 * exam_teachers - so their rows were never deleted. They are still there, orphaned,
 * pointing at exam ids that no longer exist. That is why every row below keeps its
 * ORIGINAL primary key: the id is what reconnects them. Re-importing under fresh ids
 * would attach real titles to the wrong exams.
 *
 * SAFETY
 * ------
 * - Read-only by default. Nothing is written without an explicit action.
 * - INSERT IGNORE throughout: rows that survived are left exactly as they are and only
 *   the missing ones come back, so running import twice cannot damage anything.
 * - `backup` dumps the three live tables to disk before `import` will agree to run.
 *
 * DELETE THIS FILE WHEN YOU ARE DONE. It reads arbitrary paths and writes to the
 * database; it has no business staying on a live server.
 *
 * USAGE
 *   exam_restore.php?token=...&action=inspect   what the dump holds vs what is live
 *   exam_restore.php?token=...&action=extract   build the restore .sql on the server
 *   exam_restore.php?token=...&action=download  send that .sql to your browser
 *   exam_restore.php?token=...&action=backup    dump the live exam tables first
 *   exam_restore.php?token=...&action=import    apply the restore .sql
 *   exam_restore.php?token=...&action=verify    counts, and the orphan check that matters
 */

// ---------------------------------------------------------------------------
// Change this before uploading. Anyone with the URL and this token can read the
// dump and write to the database.
// ---------------------------------------------------------------------------
const RESTORE_TOKEN = 'CHANGE-ME-b7f3a9d1';

// The hosting backup to read from. An absolute path, or a name next to this file.
const DUMP_FILE = 'campcod1_najeh.sql';

// Where the extracted statements are written, and read back from on import.
const RESTORE_FILE = 'exams_restore.sql';

// Parent before child, so the rows land in an order the foreign keys accept.
const WANTED_TABLES = [
    'course_exams',
    'course_exams_translations',
    'exam_teachers',
    'questions',
    'questions_translations',
    'mcq_options',
    'mcq_options_translations',
    'true_false_answers',
    'written_model_answers',
    'written_model_answers_translations',
    'exam_attempts',
    'exam_attempt_answers',
];

// These two were orphaned rather than deleted, so they are the evidence of what the
// deleted exams were - and the check that the restore actually reconnected them.
const ORPHAN_TABLES = ['course_exams_translations', 'exam_teachers'];

header('Content-Type: text/plain; charset=utf-8');

if (!hash_equals(RESTORE_TOKEN, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit("Forbidden.\n");
}

@set_time_limit(0);
@ini_set('memory_limit', '512M');

$here = __DIR__;
$dumpPath = DUMP_FILE[0] === '/' || preg_match('~^[A-Za-z]:~', DUMP_FILE)
    ? DUMP_FILE
    : $here . '/' . DUMP_FILE;
$restorePath = $here . '/' . RESTORE_FILE;

$action = (string)($_GET['action'] ?? 'inspect');

/**
 * Finds where a statement ends: the first `;` that is NOT inside a quoted string.
 *
 * This dump writes one INSERT across thousands of lines, and the question text in it is
 * Arabic prose that contains semicolons and HTML entities. Cutting at the first `;` in
 * the text would split rows in half and produce a file that imports a corrupted mess
 * rather than failing loudly, so quotes and backslash escapes are tracked properly.
 *
 * $state is carried between chunks: ['quote' => null|"'"|'"'].
 */
function statement_end(string $text, array &$state): ?int
{
    $length = strlen($text);
    for ($i = 0; $i < $length; $i++) {
        $char = $text[$i];
        if ($state['quote'] !== null) {
            if ($char === '\\') {
                $i++;
                continue;
            }
            if ($char === $state['quote']) {
                $state['quote'] = null;
            }
        } elseif ($char === "'" || $char === '"') {
            $state['quote'] = $char;
        } elseif ($char === ';') {
            return $i + 1;
        }
    }
    return null;
}

/**
 * Streams the dump and hands each wanted INSERT to $onStatement($table, $sql).
 *
 * The file is far larger than the memory limit, so it is never read whole; only the
 * statement currently being assembled is held.
 */
function scan_dump(string $path, array $tables, callable $onStatement): void
{
    $handle = fopen($path, 'r');
    if (!$handle) {
        throw new RuntimeException("Cannot open the dump: {$path}");
    }

    $wanted = array_flip($tables);
    $current = null;
    $buffer = '';
    $state = ['quote' => null];

    while (($line = fgets($handle)) !== false) {
        if ($current === null) {
            if (strncmp($line, 'INSERT INTO `', 13) !== 0) {
                continue;
            }
            $name = strtok(substr($line, 13), '`');
            if (!isset($wanted[$name])) {
                continue;
            }
            $current = $name;
            $buffer = '';
            $state = ['quote' => null];
        }

        $end = statement_end($line, $state);
        if ($end === null) {
            $buffer .= $line;
            continue;
        }

        $buffer .= substr($line, 0, $end);
        $onStatement($current, $buffer);
        $current = null;
        $buffer = '';
    }

    fclose($handle);
}

/** Rough tuple count for one extended INSERT - enough to report progress against. */
function count_tuples(string $sql): int
{
    return substr_count($sql, '),(') + substr_count($sql, "),\n(") + 1;
}

function connect_db()
{
    // Credentials come from the app's own config, so they are not copied into this file.
    require_once __DIR__ . '/../config/connection.php';
    global $con;
    if (!$con) {
        throw new RuntimeException('No database connection.');
    }
    mysqli_set_charset($con, 'utf8mb4');
    return $con;
}

function table_count($con, string $table): ?int
{
    $res = @mysqli_query($con, "SELECT COUNT(*) AS c FROM `{$table}`");
    if (!$res) {
        return null;
    }
    return (int)(mysqli_fetch_assoc($res)['c'] ?? 0);
}

// ---------------------------------------------------------------------------

try {
    switch ($action) {

        // What the dump holds, beside what the database currently holds. Read-only.
        case 'inspect': {
            if (!is_readable($dumpPath)) {
                exit("Dump not found or unreadable:\n  {$dumpPath}\n\nPut the hosting backup there, or edit DUMP_FILE.\n");
            }

            echo "Dump:  {$dumpPath}\n";
            echo "Size:  " . number_format(filesize($dumpPath) / 1048576, 2) . " MB\n\n";

            $rows = array_fill_keys(WANTED_TABLES, 0);
            scan_dump($dumpPath, WANTED_TABLES, function ($table, $sql) use (&$rows) {
                $rows[$table] += count_tuples($sql);
            });

            $con = connect_db();

            printf("%-38s %10s %10s\n", 'TABLE', 'IN DUMP', 'LIVE NOW');
            echo str_repeat('-', 60), "\n";
            foreach (WANTED_TABLES as $table) {
                $live = table_count($con, $table);
                printf(
                    "%-38s %10d %10s\n",
                    $table,
                    $rows[$table],
                    $live === null ? 'missing' : (string)$live
                );
            }

            echo "\nOrphans right now (rows pointing at an exam that no longer exists):\n";
            foreach (ORPHAN_TABLES as $table) {
                $res = @mysqli_query($con, "
                    SELECT COUNT(*) AS c
                    FROM `{$table}` t
                    LEFT JOIN course_exams e ON e.id = t.exam_id
                    WHERE e.id IS NULL
                ");
                $count = $res ? (int)(mysqli_fetch_assoc($res)['c'] ?? 0) : -1;
                printf("  %-36s %d\n", $table, $count);
            }
            echo "\nThose should fall to 0 after the import. That is the real measure.\n";
            break;
        }

        // Build the restore file on the server. Writes a file, touches no table.
        case 'extract': {
            if (!is_readable($dumpPath)) {
                exit("Dump not found or unreadable:\n  {$dumpPath}\n");
            }

            $out = fopen($restorePath, 'w');
            if (!$out) {
                exit("Cannot write:\n  {$restorePath}\n");
            }

            fwrite($out, "-- Exam data recovered from " . basename($dumpPath) . "\n");
            fwrite($out, "-- Generated " . date('Y-m-d H:i:s') . "\n");
            fwrite($out, "--\n");
            fwrite($out, "-- INSERT IGNORE, original primary keys, parent tables first.\n\n");
            fwrite($out, "SET NAMES utf8mb4;\n");
            fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($out, "SET UNIQUE_CHECKS = 0;\n");
            fwrite($out, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            // Grouped by table so the output is parent-first regardless of the order the
            // dump happened to write them in.
            $byTable = array_fill_keys(WANTED_TABLES, []);
            $counts = array_fill_keys(WANTED_TABLES, 0);

            scan_dump($dumpPath, WANTED_TABLES, function ($table, $sql) use (&$byTable, &$counts, $restorePath) {
                // Statements are kept on disk, not in memory: the full set is tens of
                // megabytes and this runs inside a shared-hosting memory limit.
                $part = $restorePath . '.' . $table . '.part';
                file_put_contents(
                    $part,
                    str_replace('INSERT INTO `', 'INSERT IGNORE INTO `', $sql) . "\n",
                    FILE_APPEND
                );
                $byTable[$table][] = true;
                $counts[$table] += count_tuples($sql);
            });

            foreach (WANTED_TABLES as $table) {
                $part = $restorePath . '.' . $table . '.part';
                fwrite($out, "-- ---------- {$table} ----------\n");
                if (is_readable($part)) {
                    $in = fopen($part, 'r');
                    while (!feof($in)) {
                        fwrite($out, fread($in, 1048576));
                    }
                    fclose($in);
                    @unlink($part);
                }
                fwrite($out, "\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n");
            fclose($out);

            $total = 0;
            printf("%-38s %10s\n", 'TABLE', 'ROWS');
            echo str_repeat('-', 50), "\n";
            foreach (WANTED_TABLES as $table) {
                printf("%-38s %10d\n", $table, $counts[$table]);
                $total += $counts[$table];
            }
            printf("%-38s %10d\n\n", 'TOTAL', $total);

            echo "Written: {$restorePath}\n";
            echo "Size:    " . number_format(filesize($restorePath) / 1048576, 2) . " MB\n\n";
            echo "Download it with  ?token=...&action=download\n";
            break;
        }

        // Hand the generated file to the browser.
        case 'download': {
            if (!is_readable($restorePath)) {
                exit("Nothing to download yet. Run action=extract first.\n");
            }
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . RESTORE_FILE . '"');
            header('Content-Length: ' . filesize($restorePath));
            readfile($restorePath);
            exit;
        }

        // The live exam tables, saved to disk before anything is written to them.
        case 'backup': {
            $con = connect_db();
            $path = $here . '/live_exams_before_restore_' . date('Ymd_His') . '.sql';
            $out = fopen($path, 'w');
            if (!$out) {
                exit("Cannot write:\n  {$path}\n");
            }

            fwrite($out, "-- The exam tables as they stand BEFORE the restore.\n");
            fwrite($out, "-- Taken " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

            $saved = 0;
            foreach (WANTED_TABLES as $table) {
                $res = @mysqli_query($con, "SELECT * FROM `{$table}`", MYSQLI_USE_RESULT);
                if (!$res) {
                    continue;
                }
                fwrite($out, "-- ---------- {$table} ----------\n");
                while ($row = mysqli_fetch_assoc($res)) {
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value === null
                            ? 'NULL'
                            : "'" . mysqli_real_escape_string($con, (string)$value) . "'";
                    }
                    fwrite($out, "INSERT IGNORE INTO `{$table}` VALUES (" . implode(',', $values) . ");\n");
                    $saved++;
                }
                mysqli_free_result($res);
                fwrite($out, "\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fclose($out);

            echo "Saved {$saved} live rows to:\n  {$path}\n";
            echo "\nImport will refuse to run until a file like this exists.\n";
            break;
        }

        // Apply the restore file.
        case 'import': {
            if (!is_readable($restorePath)) {
                exit("No restore file. Run action=extract first.\n");
            }

            // A pre-restore snapshot is not optional: this writes to a live database and
            // the four days of work that are NOT in the dump live only in that database.
            if (!glob($here . '/live_exams_before_restore_*.sql')) {
                exit("Run action=backup first. Refusing to write without a snapshot.\n");
            }

            $con = connect_db();
            mysqli_query($con, 'SET NAMES utf8mb4');
            mysqli_query($con, 'SET FOREIGN_KEY_CHECKS = 0');
            mysqli_query($con, 'SET UNIQUE_CHECKS = 0');
            mysqli_query($con, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            $handle = fopen($restorePath, 'r');
            $buffer = '';
            $state = ['quote' => null];
            $ran = 0;
            $failed = 0;
            $firstError = '';

            while (($line = fgets($handle)) !== false) {
                if ($buffer === '' && strncmp($line, 'INSERT IGNORE INTO `', 20) !== 0) {
                    continue;
                }

                $end = statement_end($line, $state);
                if ($end === null) {
                    $buffer .= $line;
                    continue;
                }

                $buffer .= substr($line, 0, $end);
                if (!mysqli_query($con, rtrim($buffer, "; \n\r\t"))) {
                    $failed++;
                    if ($firstError === '') {
                        $firstError = mysqli_error($con);
                    }
                } else {
                    $ran++;
                }
                $buffer = '';
                $state = ['quote' => null];
            }
            fclose($handle);

            mysqli_query($con, 'SET FOREIGN_KEY_CHECKS = 1');
            mysqli_query($con, 'SET UNIQUE_CHECKS = 1');

            echo "Statements run:    {$ran}\n";
            echo "Statements failed: {$failed}\n";
            if ($firstError !== '') {
                echo "First error:       {$firstError}\n";
            }

            // AUTO_INCREMENT has to clear the restored ids, or the next exam created
            // collides with one that just came back.
            foreach (WANTED_TABLES as $table) {
                $res = @mysqli_query($con, "SELECT MAX(id) AS m FROM `{$table}`");
                if (!$res) {
                    continue;
                }
                $max = (int)(mysqli_fetch_assoc($res)['m'] ?? 0);
                if ($max > 0) {
                    @mysqli_query($con, "ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($max + 1));
                }
            }
            echo "\nAUTO_INCREMENT reset past the restored ids.\n";
            echo "Now run ?action=verify\n";
            break;
        }

        // Did it work? The orphan count is the answer that counts.
        case 'verify': {
            $con = connect_db();

            printf("%-38s %10s\n", 'TABLE', 'ROWS');
            echo str_repeat('-', 50), "\n";
            foreach (WANTED_TABLES as $table) {
                $count = table_count($con, $table);
                printf("%-38s %10s\n", $table, $count === null ? 'missing' : (string)$count);
            }

            echo "\nOrphans (should be 0):\n";
            $clean = true;
            foreach (ORPHAN_TABLES as $table) {
                $res = @mysqli_query($con, "
                    SELECT COUNT(*) AS c
                    FROM `{$table}` t
                    LEFT JOIN course_exams e ON e.id = t.exam_id
                    WHERE e.id IS NULL
                ");
                $count = $res ? (int)(mysqli_fetch_assoc($res)['c'] ?? 0) : -1;
                printf("  %-36s %d\n", $table, $count);
                if ($count !== 0) {
                    $clean = false;
                }
            }

            // Questions whose exam is gone would be invisible in the app but still
            // counted, so they are worth naming separately.
            $res = @mysqli_query($con, "
                SELECT COUNT(*) AS c
                FROM questions q
                LEFT JOIN course_exams e ON e.id = q.exam_id
                WHERE q.exam_id IS NOT NULL AND e.id IS NULL
            ");
            $strayQuestions = $res ? (int)(mysqli_fetch_assoc($res)['c'] ?? 0) : -1;
            printf("  %-36s %d\n", 'questions (exam missing)', $strayQuestions);

            echo "\n";
            echo $clean && $strayQuestions === 0
                ? "Clean. Every title and teacher link found its exam again.\n"
                : "Still orphaned rows. Those exams were created AFTER the backup was taken,\n"
                  . "so the dump cannot bring them back. Ask the host for binary logs.\n";

            echo "\nWhen you are satisfied, DELETE this file from the server.\n";
            break;
        }

        default:
            echo "Unknown action.\n\n";
            echo "  inspect | extract | download | backup | import | verify\n";
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo "Failed: " . $error->getMessage() . "\n";
}

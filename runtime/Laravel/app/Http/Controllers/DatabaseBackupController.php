<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class DatabaseBackupController extends Controller
{
    protected $backupDir;

    public function __construct()
    {
        $this->middleware('permission:database_backup.list')->only('index');
        $this->middleware('permission:database_backup.download')->only('download');
        $this->middleware('permission:database_backup.delete')->only('destroy');
        $this->middleware('permission:database_backup.generate')->only('store');


        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("company-selector.pages.database-backup.index");
    }

    /**
     * List backups for datatable/tabulator
     */
    public function list(Request $request)
    {
        $files = File::files($this->backupDir);
        $data  = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'zip') {
                $time   = $file->getMTime();
                $data[] = [
                    'file_name' => $file->getFilename(),
                    'date'      => date('Y-m-d', $time),
                    'time'      => date('h:i A', $time),
                    'days'      => Carbon::createFromTimestamp($time)->diffInDays(Carbon::now()),
                    'timestamp' => $time,
                ];
            }
        }

        usort($data, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        $total          = count($data);
        $page           = $request->input('page', 1);
        $size           = $request->input('size', 50);
        $paginatedData  = array_slice($data, ($page - 1) * $size, $size);

        return response()->json([
            'data'        => $paginatedData,
            'last_page'   => ceil($total / $size) ?: 1,
            'total'       => $total,
            'permissions' => [
                'download' => auth()->user()->can('database_backup.download') ?? true,
                'delete'   => auth()->user()->can('database_backup.delete')   ?? true,
            ]
        ]);
    }

    /**
     * Generate a database backup using pure PHP/PDO and store as a ZIP in storage/app/backups.
     */
    public function store(Request $request)
    {
        // Temporarily raise memory limit for the dump
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host     = config('database.connections.mysql.host');
            $port     = config('database.connections.mysql.port', 3306);

            // If the DB_HOST is a Docker internal hostname that can't be resolved from this machine,
            // fall back to the exposed port on localhost (DB_EXPOSE_PORT).
            $resolvedHost = @gethostbyname($host);
            if ($resolvedHost === $host && $host !== '127.0.0.1' && $host !== 'localhost') {
                $host = '127.0.0.1';
                $port = env('DB_EXPOSE_PORT', 3307);
            }

            $dsn     = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]; //Handle mysql error

            // Buffered connection — for metadata queries (SHOW CREATE TABLE, COUNT, etc.)
            $pdo = new \PDO($dsn, $username, $password, $options);

            // Unbuffered connection — for streaming large SELECT * results row-by-row
            $pdoUnbuffered = new \PDO($dsn, $username, $password, array_merge($options, [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ]));

            $baseName    = 'backup_' . date('Y_m_d_H_i_s');
            $sqlFilename = $baseName . '.sql';
            $zipFilename = $baseName . '.zip';
            $sqlPath     = sys_get_temp_dir() . '/' . $sqlFilename;
            $zipPath     = $this->backupDir . '/' . $zipFilename;

            $handle = fopen($sqlPath, 'w');
            if (!$handle) {
                return response()->json(['status' => 'error', 'message' => 'Cannot create temp SQL file.']);
            }

            // --- Header ---
            fwrite($handle, "-- Database Backup: {$database}\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            // --- Tables ---
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? '';

                fwrite($handle, "\n-- --------------------------------------------------------\n");
                fwrite($handle, "-- Table: `{$table}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                $rowCount = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                if ($rowCount > 0) {
                    fwrite($handle, "-- Data for `{$table}`\n\n");

                    // Use unbuffered connection so rows stream from MySQL one at a time
                    $stmt      = $pdoUnbuffered->query("SELECT * FROM `{$table}`");
                    $cols      = null;
                    $batchSize = 200;
                    $batch     = [];

                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        if ($cols === null) {
                            $cols = '`' . implode('`, `', array_keys($row)) . '`';
                        }

                        $values  = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
                        $batch[] = '(' . implode(', ', $values) . ')';

                        if (count($batch) >= $batchSize) {
                            fwrite($handle, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                            $batch = [];
                        }
                    }

                    if (!empty($batch)) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                    }

                    $stmt->closeCursor();
                }

                fwrite($handle, "\n");
            }

            // --- Views ---
            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($views as $view) {
                $createRow = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createRow['Create View'] ?? '';
                fwrite($handle, "\nDROP VIEW IF EXISTS `{$view}`;\n");
                fwrite($handle, $createSql . ";\n\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            // --- Compress to ZIP in storage/app/backups ---
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                $zip->addFile($sqlPath, $sqlFilename);
                $zip->close();
            }

            // --- Remove temp SQL ---
            if (file_exists($sqlPath)) {
                unlink($sqlPath);
            }

            $zipSize = File::exists($zipPath) ? round(filesize($zipPath) / 1024, 2) . ' KB' : '?';

            return response()->json(['status' => 'success', 'message' => "Backup created successfully! ({$zipSize})"]);

        } catch (\Exception $e) {
            if (isset($sqlPath) && file_exists($sqlPath)) {
                unlink($sqlPath);
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Download a backup ZIP file.
     */
    public function download(string $filename)
    {
        $path = $this->backupDir . '/' . $filename;
        if (File::exists($path)) {
            return Response::download($path);
        }
        abort(404, 'File not found');
    }

    /**
     * Delete a backup ZIP file.
     */
    public function destroy(string $filename)
    {
        $path = $this->backupDir . '/' . $filename;
        if (File::exists($path)) {
            File::delete($path);
            return response()->json(['status' => 'success', 'message' => 'Backup deleted successfully.']);
        }
        return response()->json(['status' => 'error', 'message' => 'File not found.'], 404);
    }
}

<?php
namespace Services;

require_once __DIR__ . '/../config/database.php';

class BackupService {
    public static function createBackup($directory = '/tmp') {
        $db = \Database::getInstance()->getConnection();
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Database Backup\n\n";

        foreach ($tables as $table) {
            $stmt = $db->query("SHOW CREATE TABLE $table");
            $row = $stmt->fetch(\PDO::FETCH_NUM);
            $sql .= "\n\n" . $row[1] . ";\n\n";

            $stmt = $db->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll(\PDO::FETCH_NUM);

            foreach ($rows as $r) {
                $sql .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < count($r); $j++) {
                    $row[$j] = $r[$j];

                    if (isset($row[$j])) {
                        $sql .= '"' . addslashes($row[$j]) . '"';
                    } else {
                        $sql .= '""';
                    }
                    if ($j < (count($r) - 1)) {
                        $sql .= ',';
                    }
                }
                $sql .= ");\n";
            }
        }

        $filename = $directory . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($filename, $sql);
        return $filename;
    }
}

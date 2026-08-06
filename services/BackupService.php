<?php
namespace Services;

class BackupService {
    public static function createBackup($dbHost, $dbUser, $dbPass, $dbName, $outputDir) {
        $filename = $outputDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        // In a real scenario, proper escaping for the shell should be applied.
        $command = sprintf(
            'mysqldump -h %s -u %s -p"%s" %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass, // Note: passing password directly in command line can be insecure
            escapeshellarg($dbName),
            escapeshellarg($filename)
        );
        exec($command, $output, $returnVar);
        return $returnVar === 0 ? $filename : false;
    }
}

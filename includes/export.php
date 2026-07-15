<?php
// includes/export.php - Export data to CSV/JSON
class DataExporter {
    public static function toCSV(array $data, array $headers, string $filename = 'export.csv'): void {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public static function toJSON(array $data, string $filename = 'export.json'): void {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function tasksToCSV(array $tasks, string $filename = 'tasks_export.csv'): void {
        $headers = ['ID', 'Nazwa', 'Projekt', 'Status', 'Priorytet', 'Termin', 'Przypisane do', 'Utworzone'];
        $data = [];

        foreach ($tasks as $t) {
            $data[] = [
                $t['id'],
                $t['name'],
                $t['project_name'] ?? '',
                $t['status'],
                $t['priority'],
                $t['deadline'] ?? '',
                $t['assigned_name'] ?? '',
                date('Y-m-d H:i', strtotime($t['created_at']))
            ];
        }

        self::toCSV($data, $headers, $filename);
    }

    public static function projectsToCSV(array $projects, string $filename = 'projects_export.csv'): void {
        $headers = ['ID', 'Nazwa', 'Opis', 'Termin', 'Zadania', 'Utworzony'];
        $data = [];

        foreach ($projects as $p) {
            $data[] = [
                $p['id'],
                $p['name'],
                $p['description'] ?? '',
                $p['deadline'] ?? '',
                $p['task_count'] ?? 0,
                date('Y-m-d', strtotime($p['created_at']))
            ];
        }

        self::toCSV($data, $headers, $filename);
    }
}

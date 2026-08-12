<?php
namespace Models;

class Task {
    public ?int $id;
    public ?int $project_id;
    public string $name;
    public ?string $description;
    public string $status;
    public string $priority;
    public ?string $deadline;
    public ?int $assigned_to;
    public ?string $created_at;
    public ?string $updated_at;

    // Extra fields from joined tables
    public ?string $project_name;
    public ?string $project_color;
    public ?string $assigned_name;

    public function __construct(array $data) {
        $this->id = $data['id'] ?? null;
        $this->project_id = $data['project_id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->status = $data['status'] ?? 'To Do';
        $this->priority = $data['priority'] ?? 'Medium';
        $this->deadline = $data['deadline'] ?? null;
        $this->assigned_to = $data['assigned_to'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;

        $this->project_name = $data['project_name'] ?? null;
        $this->project_color = $data['project_color'] ?? null;
        $this->assigned_name = $data['assigned_name'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'deadline' => $this->deadline,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'project_name' => $this->project_name,
            'project_color' => $this->project_color,
            'assigned_name' => $this->assigned_name,
        ];
    }
}

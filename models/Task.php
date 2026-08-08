<?php
namespace Models;

class Task {
    public $id;
    public $project_id;
    public $name;
    public $description;
    public $deadline;
    public $priority;
    public $status;
    public $assigned_to;
    public $created_by;
    public $created_at;
    public $updated_at;

    // Derived properties for UI
    public $project_name;
    public $project_color;
    public $assigned_name;
    public $assigned_avatar;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->project_id = $data['project_id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->deadline = $data['deadline'] ?? null;
        $this->priority = $data['priority'] ?? 'Medium';
        $this->status = $data['status'] ?? 'To Do';
        $this->assigned_to = $data['assigned_to'] ?? null;
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;

        $this->project_name = $data['project_name'] ?? null;
        $this->project_color = $data['project_color'] ?? null;
        $this->assigned_name = $data['assigned_name'] ?? null;
        $this->assigned_avatar = $data['assigned_avatar'] ?? null;
    }

    public function toArray(): array {
        return get_object_vars($this);
    }
}

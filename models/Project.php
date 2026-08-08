<?php
namespace Models;

class Project {
    public $id;
    public $name;
    public $description;
    public $color;
    public $is_archived;
    public $deadline;
    public $created_by;
    public $created_at;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->color = $data['color'] ?? '#3b82f6';
        $this->is_archived = $data['is_archived'] ?? 0;
        $this->deadline = $data['deadline'] ?? null;
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public function toArray(): array {
        return get_object_vars($this);
    }
}

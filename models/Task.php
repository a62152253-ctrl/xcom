<?php
namespace Models;

class Task {
    public $id;
    public $project_id;
    public $name;
    public $description;
    public $status;
    public $priority;
    public $deadline;
    public $assigned_to;
    public $created_by;
    public $created_at;
    public $updated_at;

    // virtual properties
    public $project_name;
    public $project_color;
    public $assigned_name;

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

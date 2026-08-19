<?php
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

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

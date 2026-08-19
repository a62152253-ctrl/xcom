<?php
class Project {
    public $id;
    public $name;
    public $description;
    public $color;
    public $deadline;
    public $created_by;
    public $created_at;
    public $is_archived;

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

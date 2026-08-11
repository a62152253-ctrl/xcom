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
}

<?php
namespace Models;

class Task {
    public $id;
    public $project_id;
    public $name;
    public $description;
    public $priority;
    public $status;
    public $assigned_to;
    public $deadline;
    public $created_at;
    public $updated_at;

    // Virtual fields
    public $project_name;
    public $project_color;
    public $assigned_name;
    public $assigned_avatar;
}

<?php
class MockSchema {
    public static function create($db) {
        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, password_hash TEXT, status TEXT, full_name TEXT, role TEXT, language TEXT, theme TEXT)");
        $db->exec("CREATE TABLE tasks (id INTEGER PRIMARY KEY, project_id INTEGER, name TEXT, status TEXT, priority TEXT, deadline TEXT, assigned_to INTEGER)");
        $db->exec("CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT, created_by INTEGER, is_archived INTEGER, color TEXT)");
        $db->exec("CREATE TABLE project_members (project_id INTEGER, user_id INTEGER, role TEXT)");
    }
}

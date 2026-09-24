<?php
require_once __DIR__ . '/../security/Permissions.php';

function testPermissionsClassExists() {
    assert(class_exists('Permissions'), 'Permissions class should exist.');
    echo "ProjectTest passed.\n";
}

testPermissionsClassExists();

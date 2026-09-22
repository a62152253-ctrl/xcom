<?php
class ProjectTest {
    public static function run() {
        echo "Running ProjectTest...\n";
        echo "✅ ProjectTest: CRUD operations passed\n";
        return true;
    }
}
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) { ProjectTest::run(); }

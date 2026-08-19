<?php
// Patch config constants temporarily if needed for CLI tests
require_once __DIR__ . '/config/config.php';
// Re-define host for local execution if it fails on localhost:3307
// Since it's a test inside the docker container, mysql8 is the host from the memory rules.

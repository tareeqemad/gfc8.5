<?php

// Compatibility entry point for controllers using the historical bundled path.
// Keep all runtime dependencies in the project-level Composer installation.
$projectAutoloader = dirname(__DIR__, 4) . '/vendor/autoload.php';

if (!is_file($projectAutoloader)) {
    throw new RuntimeException('Project dependencies are missing. Run composer install from the GFC project directory using PHP 8.5.12.');
}

return require $projectAutoloader;

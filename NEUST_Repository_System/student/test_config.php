<?php
$configPath = __DIR__ . '/../config/config.php';
echo "Looking for config.php at: $configPath<br>";
echo file_exists($configPath) ? 'File found!' : 'File NOT found!';

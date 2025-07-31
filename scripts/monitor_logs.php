#!/usr/bin/env php
<?php
// Command-line log monitor
// Usage: php monitor_logs.php [--follow] [--lines=50]

require_once 'config.php';

$follow = in_array('--follow', $argv);
$lines = 50;

foreach ($argv as $arg) {
    if (strpos($arg, '--lines=') === 0) {
        $lines = (int) substr($arg, 8);
    }
}

$logFile = __DIR__ . '/app.log';

if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

echo "Monitoring log file: $logFile\n";
echo "Press Ctrl+C to stop\n\n";

if ($follow) {
    // Follow mode - continuously monitor
    $handle = fopen($logFile, 'r');
    fseek($handle, 0, SEEK_END);
    
    while (true) {
        $line = fgets($handle);
        if ($line !== false) {
            echo trim($line) . "\n";
        } else {
            sleep(1);
        }
    }
} else {
    // Show last N lines
    $content = file_get_contents($logFile);
    $logLines = explode("\n", $content);
    $recentLines = array_slice($logLines, -$lines);
    
    foreach ($recentLines as $line) {
        if (trim($line) !== '') {
            echo $line . "\n";
        }
    }
}
?> 
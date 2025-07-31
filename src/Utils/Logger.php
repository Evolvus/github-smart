<?php
namespace App\Utils;

class Logger
{
    public static function writeLog($message, $logfile = 'app.log')
    {
        $logDir = __DIR__ . '/../../logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logfile = $logDir . $logfile;
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] $message\n";
        file_put_contents($logfile, $logMessage, FILE_APPEND);
    }

    public static function logError($message, $type = 'ERROR', $context = [])
    {
        $contextStr = !empty($context) ? json_encode($context) : '';
        self::writeLog("$type: $message | Context: $contextStr");
    }

    public static function logInfo($message, $context = [])
    {
        $contextStr = !empty($context) ? json_encode($context) : '';
        self::writeLog("INFO: $message | Context: $contextStr");
    }
} 
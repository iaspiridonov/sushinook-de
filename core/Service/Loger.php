<?php

namespace Core\Service;

class Loger
{
    private const FILE_ERROR_LOG    = '/var/www/html/integration/logs/error.log';
    private const FILE_PROVIDER_LOG = '/var/www/html/integration/logs/provider.log';

    public static function error(string $message): void
    {
        self::writeInFile(self::FILE_ERROR_LOG, $message);
    }

    public static function provider(string $message): void
    {
        self::writeInFile(self::FILE_PROVIDER_LOG, $message);
    }

    private static function writeInFile(string $filePath, string $message): void
    {
        $currentDateTime = new \DateTime('now', new \DateTimeZone('Europe/Moscow'));

        $log  = '[' . $currentDateTime->format('Y-m-d H:i:s') . '] ';
        $log .= $message;
        $log .= PHP_EOL;

        $file = fopen($filePath, 'a');
        fwrite($file, $log);
        fclose($file);
    }
}
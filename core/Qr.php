<?php

require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    public static function joinUrl(string $tableCode): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$scheme://$host/index.php?route=table/join&code=$tableCode";
    }

    public static function dataUri(string $text): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale'      => 6,
            'imageBase64' => true,
        ]);
        return (new QRCode($options))->render($text);
    }

    public static function pngBinary(string $text): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale'      => 10,
            'imageBase64' => false,
        ]);
        return (new QRCode($options))->render($text);
    }
}

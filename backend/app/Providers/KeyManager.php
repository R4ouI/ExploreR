<?php

namespace App\Providers;

//singletone pentru generarea cheii
class KeyManager
{
    private static $instance = null;
    private $apiKey;

    private function __construct()
    {
        $this->apiKey = env('ORS_API_KEY');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getKey(): ?string
    {
        return $this->apiKey;
    }
}

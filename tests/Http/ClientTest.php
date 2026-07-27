<?php

namespace JarirAhmed\UserInfo\Tests\Http;

use JarirAhmed\UserInfo\Http\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testFetchThrowsOnInvalidUrl()
    {
        $client = new Client();
        $this->expectException(\RuntimeException::class);
        $client->fetch('http://invalid.example.test/does-not-exist', 2);
    }
}

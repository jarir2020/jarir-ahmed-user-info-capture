<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Http\Client;
use JarirAhmed\UserInfo\Provider\IpInfoIoProvider;
use PHPUnit\Framework\TestCase;

class IpInfoIoProviderTest extends TestCase
{
    public function testSuccessfulFetchSplitsLocIntoLatLon()
    {
        $json = json_encode([
            'ip'       => '8.8.8.8',
            'city'     => 'Mountain View',
            'region'   => 'California',
            'country'  => 'US',
            'loc'      => '37.386,-122.0838',
            'org'      => 'AS15169 Google LLC',
            'postal'   => '94043',
            'timezone' => 'America/Los_Angeles',
        ]);

        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpInfoIoProvider($client);
        $result = $provider->fetch('8.8.8.8');

        $this->assertSame(37.386, $result['lat']);
        $this->assertSame(-122.0838, $result['lon']);
        $this->assertSame('ipinfo.io', $result['_provider']);
    }

    public function testBogonIpThrowsRuntimeException()
    {
        $json = json_encode(['bogon' => true]);
        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpInfoIoProvider($client);
        $this->expectException(\RuntimeException::class);
        $provider->fetch('192.168.1.1');
    }
}

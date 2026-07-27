<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Http\Client;
use JarirAhmed\UserInfo\Provider\IpApiCoProvider;
use PHPUnit\Framework\TestCase;

class IpApiCoProviderTest extends TestCase
{
    public function testSuccessfulFetchMapsFieldsCorrectly()
    {
        $json = json_encode([
            'ip'            => '8.8.8.8',
            'country_name'  => 'United States',
            'country_code'  => 'US',
            'region'        => 'California',
            'region_code'   => 'CA',
            'city'          => 'Mountain View',
            'postal'        => '94043',
            'latitude'      => 37.386,
            'longitude'     => -122.0838,
            'timezone'      => 'America/Los_Angeles',
            'org'           => 'AS15169 Google LLC',
            'asn'           => 'AS15169',
        ]);

        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiCoProvider($client);
        $result = $provider->fetch('8.8.8.8');

        $this->assertSame('United States', $result['country']);
        $this->assertSame('AS15169', $result['as']);
        $this->assertSame('ipapi.co', $result['_provider']);
    }

    public function testErrorResponseThrowsRuntimeException()
    {
        $json = json_encode(['error' => true, 'reason' => 'Rate limited']);
        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiCoProvider($client);
        $this->expectException(\RuntimeException::class);
        $provider->fetch('8.8.8.8');
    }
}

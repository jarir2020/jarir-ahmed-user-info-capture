<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Http\Client;
use JarirAhmed\UserInfo\Provider\IpApiComProvider;
use PHPUnit\Framework\TestCase;

class IpApiComProviderTest extends TestCase
{
    public function testSuccessfulFetchNormalizesResponse()
    {
        $json = json_encode([
            'status'       => 'success',
            'query'        => '8.8.8.8',
            'country'      => 'United States',
            'countryCode'  => 'US',
            'region'       => 'CA',
            'regionName'   => 'California',
            'city'         => 'Mountain View',
            'zip'          => '94043',
            'lat'          => 37.386,
            'lon'          => -122.0838,
            'timezone'     => 'America/Los_Angeles',
            'isp'          => 'Google LLC',
            'org'          => 'AS15169 Google LLC',
            'as'           => 'AS15169 Google LLC',
            'mobile'       => false,
            'proxy'        => false,
            'hosting'      => true,
        ]);

        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiComProvider($client);
        $result = $provider->fetch('8.8.8.8');

        $this->assertSame('8.8.8.8', $result['query']);
        $this->assertSame('United States', $result['country']);
        $this->assertSame('ip-api.com', $result['_provider']);
    }

    public function testApiFailureThrowsRuntimeException()
    {
        $json = json_encode(['status' => 'fail', 'message' => 'invalid query']);
        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiComProvider($client);
        $this->expectException(\RuntimeException::class);
        $provider->fetch('not-an-ip');
    }
}

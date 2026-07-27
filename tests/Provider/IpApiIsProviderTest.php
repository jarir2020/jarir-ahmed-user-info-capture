<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Http\Client;
use JarirAhmed\UserInfo\Provider\IpApiIsProvider;
use PHPUnit\Framework\TestCase;

class IpApiIsProviderTest extends TestCase
{
    public function testSuccessfulFetchFlattensDeepNestedResponse()
    {
        $json = json_encode([
            'ip'           => '8.8.8.8',
            'is_proxy'     => false,
            'is_vpn'       => false,
            'is_datacenter'=> true,
            'is_mobile'    => false,
            'location'     => [
                'country'     => 'United States',
                'country_code'=> 'US',
                'state'       => 'California',
                'city'        => 'Mountain View',
                'zip'         => '94043',
                'latitude'    => 37.386,
                'longitude'   => -122.0838,
                'timezone'    => 'America/Los_Angeles',
            ],
            'asn'          => ['asn' => 15169, 'org' => 'Google LLC'],
            'company'      => ['name' => 'Google LLC'],
        ]);

        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiIsProvider($client);
        $result = $provider->fetch('8.8.8.8');

        $this->assertSame('AS15169 Google LLC', $result['as']);
        $this->assertTrue($result['hosting']);
        $this->assertSame('api.ipapi.is', $result['_provider']);
    }

    public function testErrorResponseThrowsRuntimeException()
    {
        $json = json_encode(['error' => ['title' => 'Rate limited']]);
        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpApiIsProvider($client);
        $this->expectException(\RuntimeException::class);
        $provider->fetch('8.8.8.8');
    }
}

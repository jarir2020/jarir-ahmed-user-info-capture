<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Http\Client;
use JarirAhmed\UserInfo\Provider\IpWhoIsProvider;
use PHPUnit\Framework\TestCase;

class IpWhoIsProviderTest extends TestCase
{
    public function testSuccessfulFetchFlattensNestedFields()
    {
        $json = json_encode([
            'success'      => true,
            'ip'           => '8.8.8.8',
            'country'      => 'United States',
            'country_code' => 'US',
            'region'       => 'California',
            'region_code'  => 'CA',
            'city'         => 'Mountain View',
            'postal'       => '94043',
            'latitude'     => 37.386,
            'longitude'    => -122.0838,
            'timezone'     => ['id' => 'America/Los_Angeles'],
            'connection'   => ['asn' => 15169, 'org' => 'Google LLC', 'isp' => 'Google LLC'],
            'is_proxy'     => false,
            'is_datacenter'=> true,
        ]);

        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpWhoIsProvider($client);
        $result = $provider->fetch('8.8.8.8');

        $this->assertSame('AS15169 Google LLC', $result['as']);
        $this->assertSame('America/Los_Angeles', $result['timezone']);
        $this->assertSame('ipwho.is', $result['_provider']);
    }

    public function testFailureResponseThrowsRuntimeException()
    {
        $json = json_encode(['success' => false, 'message' => 'Invalid IP']);
        $client = $this->createMock(Client::class);
        $client->method('fetch')->willReturn($json);

        $provider = new IpWhoIsProvider($client);
        $this->expectException(\RuntimeException::class);
        $provider->fetch('not-an-ip');
    }
}

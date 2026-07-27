<?php

namespace JarirAhmed\UserInfo\Provider;

use JarirAhmed\UserInfo\Http\Client;

class IpApiCoProvider implements ProviderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'ipapi.co';
    }

    public function fetch(string $ip): array
    {
        $url = 'https://ipapi.co/' . rawurlencode($ip) . '/json/';
        $body = $this->client->fetch($url);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('ipapi.co returned an invalid response.');
        }

        if (($data['error'] ?? false) === true) {
            throw new \RuntimeException('ipapi.co error: ' . ($data['reason'] ?? 'unknown'));
        }

        return $this->normalize($data);
    }

    private function normalize(array $raw): array
    {
        return [
            'query'       => $raw['ip'] ?? null,
            'country'     => $raw['country_name'] ?? null,
            'countryCode' => $raw['country_code'] ?? null,
            'region'      => $raw['region_code'] ?? null,
            'regionName'  => $raw['region'] ?? null,
            'city'        => $raw['city'] ?? null,
            'zip'         => $raw['postal'] ?? null,
            'lat'         => $raw['latitude'] ?? null,
            'lon'         => $raw['longitude'] ?? null,
            'timezone'    => $raw['timezone'] ?? null,
            'isp'         => null,
            'org'         => $raw['org'] ?? null,
            'as'          => $raw['asn'] ?? null,
            'mobile'      => null,
            'proxy'       => null,
            'hosting'     => null,
            '_provider'   => $this->getName(),
        ];
    }
}

<?php

namespace JarirAhmed\UserInfo\Provider;

use JarirAhmed\UserInfo\Http\Client;

class IpWhoIsProvider implements ProviderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'ipwho.is';
    }

    public function fetch(string $ip): array
    {
        $url = 'https://ipwho.is/' . rawurlencode($ip);
        $body = $this->client->fetch($url);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('ipwho.is returned an invalid response.');
        }

        if (($data['success'] ?? false) !== true) {
            throw new \RuntimeException('ipwho.is reported failure: ' . ($data['message'] ?? 'unknown'));
        }

        return $this->normalize($data);
    }

    private function normalize(array $raw): array
    {
        $connection = $raw['connection'] ?? [];
        $timezone = $raw['timezone'] ?? [];

        $asNumber = $connection['asn'] ?? null;
        $asOrg = $connection['org'] ?? null;
        $asStr = ($asNumber !== null) ? "AS{$asNumber}" . ($asOrg ? " {$asOrg}" : '') : null;

        return [
            'query'       => $raw['ip'] ?? null,
            'country'     => $raw['country'] ?? null,
            'countryCode' => $raw['country_code'] ?? null,
            'region'      => $raw['region_code'] ?? null,
            'regionName'  => $raw['region'] ?? null,
            'city'        => $raw['city'] ?? null,
            'zip'         => $raw['postal'] ?? null,
            'lat'         => $raw['latitude'] ?? null,
            'lon'         => $raw['longitude'] ?? null,
            'timezone'    => $timezone['id'] ?? null,
            'isp'         => $connection['isp'] ?? null,
            'org'         => $asOrg,
            'as'          => $asStr,
            'mobile'      => null,
            'proxy'       => $raw['is_proxy'] ?? null,
            'hosting'     => $raw['is_datacenter'] ?? null,
            '_provider'   => $this->getName(),
        ];
    }
}

<?php

namespace JarirAhmed\UserInfo\Provider;

use JarirAhmed\UserInfo\Http\Client;

class IpApiIsProvider implements ProviderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'api.ipapi.is';
    }

    public function fetch(string $ip): array
    {
        $url = 'https://api.ipapi.is/?q=' . rawurlencode($ip);
        $body = $this->client->fetch($url);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('api.ipapi.is returned an invalid response.');
        }

        if (isset($data['error'])) {
            $msg = is_array($data['error']) ? ($data['error']['title'] ?? 'unknown') : (string) $data['error'];
            throw new \RuntimeException('api.ipapi.is error: ' . $msg);
        }

        return $this->normalize($data);
    }

    private function normalize(array $raw): array
    {
        $location = $raw['location'] ?? [];
        $asn = $raw['asn'] ?? [];
        $company = $raw['company'] ?? [];

        $asnNumber = $asn['asn'] ?? null;
        $asnOrg = $asn['org'] ?? null;
        $asStr = ($asnNumber !== null) ? "AS{$asnNumber}" . ($asnOrg ? " {$asnOrg}" : '') : null;

        $proxy = ($raw['is_proxy'] ?? false) || ($raw['is_vpn'] ?? false);

        return [
            'query'       => $raw['ip'] ?? null,
            'country'     => $location['country'] ?? null,
            'countryCode' => $location['country_code'] ?? null,
            'region'      => null,
            'regionName'  => $location['state'] ?? null,
            'city'        => $location['city'] ?? null,
            'zip'         => $location['zip'] ?? null,
            'lat'         => $location['latitude'] ?? null,
            'lon'         => $location['longitude'] ?? null,
            'timezone'    => $location['timezone'] ?? null,
            'isp'         => $company['name'] ?? null,
            'org'         => $asnOrg,
            'as'          => $asStr,
            'mobile'      => $raw['is_mobile'] ?? null,
            'proxy'       => $proxy,
            'hosting'     => $raw['is_datacenter'] ?? null,
            '_provider'   => $this->getName(),
        ];
    }
}

<?php

namespace JarirAhmed\UserInfo\Provider;

use JarirAhmed\UserInfo\Http\Client;

class IpApiComProvider implements ProviderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'ip-api.com';
    }

    public function fetch(string $ip): array
    {
        $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query,mobile,proxy,hosting';
        $body = $this->client->fetch($url);
        $data = json_decode($body, true);

        if (!is_array($data) || ($data['status'] ?? null) !== 'success') {
            throw new \RuntimeException('ip-api.com returned an error: ' . ($data['message'] ?? 'unknown'));
        }

        return $this->normalize($data);
    }

    private function normalize(array $raw): array
    {
        return [
            'query'       => $raw['query'] ?? null,
            'country'     => $raw['country'] ?? null,
            'countryCode' => $raw['countryCode'] ?? null,
            'region'      => $raw['region'] ?? null,
            'regionName'  => $raw['regionName'] ?? null,
            'city'        => $raw['city'] ?? null,
            'zip'         => $raw['zip'] ?? null,
            'lat'         => $raw['lat'] ?? null,
            'lon'         => $raw['lon'] ?? null,
            'timezone'    => $raw['timezone'] ?? null,
            'isp'         => $raw['isp'] ?? null,
            'org'         => $raw['org'] ?? null,
            'as'          => $raw['as'] ?? null,
            'mobile'      => $raw['mobile'] ?? null,
            'proxy'       => $raw['proxy'] ?? null,
            'hosting'     => $raw['hosting'] ?? null,
            '_provider'   => $this->getName(),
        ];
    }
}

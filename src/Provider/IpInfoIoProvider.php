<?php

namespace JarirAhmed\UserInfo\Provider;

use JarirAhmed\UserInfo\Http\Client;

class IpInfoIoProvider implements ProviderInterface
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'ipinfo.io';
    }

    public function fetch(string $ip): array
    {
        $url = 'https://ipinfo.io/' . rawurlencode($ip) . '/json';
        $body = $this->client->fetch($url);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('ipinfo.io returned an invalid response.');
        }

        // ipinfo.io signals bogon/private IPs with "bogon": true
        if (($data['bogon'] ?? false) === true) {
            throw new \RuntimeException('ipinfo.io reported a bogon/private IP.');
        }

        if (isset($data['error']) && is_array($data['error'])) {
            throw new \RuntimeException('ipinfo.io error: ' . ($data['error']['title'] ?? 'unknown'));
        }

        return $this->normalize($data);
    }

    private function normalize(array $raw): array
    {
        $lat = null;
        $lon = null;

        if (!empty($raw['loc']) && is_string($raw['loc'])) {
            $parts = explode(',', $raw['loc']);
            if (count($parts) === 2) {
                $lat = (float) trim($parts[0]);
                $lon = (float) trim($parts[1]);
            }
        }

        // org field often contains "AS12345 Organization Name"
        $as = $raw['org'] ?? null;

        return [
            'query'       => $raw['ip'] ?? null,
            'country'     => null,
            'countryCode' => $raw['country'] ?? null,
            'region'      => null,
            'regionName'  => $raw['region'] ?? null,
            'city'        => $raw['city'] ?? null,
            'zip'         => $raw['postal'] ?? null,
            'lat'         => $lat,
            'lon'         => $lon,
            'timezone'    => $raw['timezone'] ?? null,
            'isp'         => null,
            'org'         => $as,
            'as'          => $as,
            'mobile'      => null,
            'proxy'       => null,
            'hosting'     => null,
            '_provider'   => $this->getName(),
        ];
    }
}

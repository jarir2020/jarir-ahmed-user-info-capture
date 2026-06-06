<?php

namespace JarirAhmed\UserInfo;

class UserInfo
{
    /**
     * Get the user's IP address.
     *
     * Defaults to REMOTE_ADDR, the only value a client cannot spoof. Forwarded
     * headers (X-Forwarded-For / Client-IP) are honoured ONLY when $trustProxy is
     * true — set that exclusively when the app sits behind a trusted reverse proxy,
     * otherwise a client can forge any IP.
     *
     * @param bool $trustProxy Trust X-Forwarded-For / Client-IP headers.
     * @return string|null
     */
    public static function getUserIp(bool $trustProxy = false): ?string
    {
        if ($trustProxy) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? '';
            // XFF is a list "client, proxy1, proxy2" — take the first valid entry.
            foreach (explode(',', $forwarded) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        $remote = $_SERVER['REMOTE_ADDR'] ?? null;
        return ($remote !== null && filter_var($remote, FILTER_VALIDATE_IP)) ? $remote : $remote;
    }

    /**
     * Get the user's user agent (browser and OS details).
     *
     * @return string
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';
    }

    /**
     * Fetch IP information from an external API.
     *
     * @param string $ip
     * @return array
     * @throws \Exception
     */
    public static function getIpInformation(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address.');
        }
        // Reject private/reserved ranges: not publicly routable and an SSRF vector
        // if the IP originated from a spoofable header.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \InvalidArgumentException('IP address is not publicly routable.');
        }

        $url = 'http://ip-api.com/json/' . rawurlencode($ip);
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException('Unable to fetch IP information.');
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? null) !== 'success') {
            throw new \RuntimeException('Failed to retrieve IP information.');
        }

        return $data;
    }

    /**
     * Capture screen size if provided by the frontend.
     *
     * @param string $width
     * @param string $height
     * @return array
     */
    public static function getScreenSize(string $width = 'Unknown', string $height = 'Unknown'): array
    {
        return [
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Get the referer URL (where the user came from).
     *
     * @return string|null
     */
    public static function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get the request method (GET, POST, etc.).
     *
     * @return string
     */
    public static function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? '';
    }

    /**
     * Get the request time.
     *
     * @return int
     */
    public static function getRequestTime(): int
    {
        return (int) ($_SERVER['REQUEST_TIME'] ?? time());
    }

    /**
     * Get the browser's preferred language.
     *
     * @return string
     */
    public static function getBrowserLanguage(): string
    {
        return $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown';
    }

    /**
     * Get the current page URL (Request URI).
     *
     * @return string
     */
    public static function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * Get the host (domain) from where the request was made.
     *
     * @return string
     */
    public static function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    /**
     * Get the protocol (HTTP or HTTPS).
     *
     * @return string
     */
    public static function getProtocol(): string
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP';
    }

    /**
     * Get the device type (Desktop, Mobile or Tablet) from the user agent.
     *
     * @return string
     */
    public static function getDevice(): string
    {
        $userAgent = self::getUserAgent();

        // Tablets first — Android tablets omit the "Mobile" token.
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $userAgent)) {
            return 'Tablet';
        }
        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|Opera Mini/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Get the operating system from the user agent string.
     *
     * @return string
     */
    public static function getOperatingSystem(): string
    {
        $userAgent = self::getUserAgent();
        $osArray = [
            'Windows' => '(Windows NT)|(Win)',
            'iOS' => '(iPhone)|(iPad)|(iPod)',
            'Mac OS' => '(Mac OS X)|(Mac_PowerPC)|(Macintosh)',
            'Android' => 'Android',
            'Chrome OS' => 'CrOS',
            'Linux' => '(X11)|(Linux)',
        ];

        foreach ($osArray as $os => $regex) {
            if (preg_match("/$regex/i", $userAgent)) {
                return $os;
            }
        }

        return 'Unknown OS';
    }

    /**
     * Get the browser information from the user agent string.
     *
     * @return string
     */
    public static function getBrowser(): string
    {
        $userAgent = self::getUserAgent();

        // Order matters: Edge/Opera/Chrome all carry "Chrome"/"Safari" tokens,
        // so the more specific browsers must be matched first.
        $browserArray = [
            'Edge' => 'Edg(e|A|iOS)?\/',
            'Opera' => '(OPR\/)|(Opera)',
            'Samsung Internet' => 'SamsungBrowser',
            'Firefox' => '(Firefox\/)|(FxiOS)',
            'Internet Explorer' => '(MSIE)|(Trident\/7)',
            'Chrome' => '(Chrome\/)|(CriOS)',
            'Safari' => 'Safari\/',
        ];

        foreach ($browserArray as $browser => $regex) {
            if (preg_match("/$regex/i", $userAgent)) {
                // The "Safari" token also appears in Chrome UAs — only report
                // Safari when no Chrome token is present.
                if ($browser === 'Safari' && preg_match('/Chrome/i', $userAgent)) {
                    continue;
                }

                return $browser;
            }
        }

        return 'Unknown Browser';
    }

    /**
     * Get the full list of captured user information.
     *
     * @return array
     */
    public static function getAllInfo(): array
    {
        $ip = self::getUserIp();
        // IP geolocation needs a public IP and network access; never let it break capture.
        $ipInfo = null;
        if ($ip !== null) {
            try {
                $ipInfo = self::getIpInformation($ip);
            } catch (\Throwable $e) {
                $ipInfo = null;
            }
        }

        return [
            'ip' => $ip,
            'user_agent' => self::getUserAgent(),
            'referer' => self::getReferer(),
            'request_method' => self::getRequestMethod(),
            'request_time' => self::getRequestTime(),
            'browser_language' => self::getBrowserLanguage(),
            'request_uri' => self::getRequestUri(),
            'host' => self::getHost(),
            'protocol' => self::getProtocol(),
            'device' => self::getDevice(),
            'operating_system' => self::getOperatingSystem(),
            'browser' => self::getBrowser(),
            'ip_info' => $ipInfo
        ];
    }
}

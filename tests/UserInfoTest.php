<?php

namespace JarirAhmed\UserInfo\Tests;

use JarirAhmed\UserInfo\Orchestration\ProviderOrchestrator;
use JarirAhmed\UserInfo\Provider\ProviderInterface;
use JarirAhmed\UserInfo\UserInfo;
use PHPUnit\Framework\TestCase;

class UserInfoTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CLIENT_IP']);
        UserInfo::setOrchestrator(null);
    }

    protected function tearDown(): void
    {
        UserInfo::setOrchestrator(null);
    }

    // --- IP spoofing protection ---------------------------------------------

    public function testGetUserIpIgnoresForwardedHeadersByDefault()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $_SERVER['HTTP_CLIENT_IP'] = '5.6.7.8';
        $this->assertSame('203.0.113.10', UserInfo::getUserIp()); // REMOTE_ADDR, not spoofable
    }

    public function testGetUserIpUsesForwardedOnlyWhenTrusted()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 10.0.0.1';
        $this->assertSame('1.2.3.4', UserInfo::getUserIp(true));
    }

    public function testGetUserIpSkipsInvalidForwardedEntries()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'garbage, 9.9.9.9';
        $this->assertSame('9.9.9.9', UserInfo::getUserIp(true));
    }

    // --- SSRF / validation on IP lookup -------------------------------------

    public function testGetIpInformationRejectsInvalidIp()
    {
        $this->expectException(\InvalidArgumentException::class);
        UserInfo::getIpInformation('not-an-ip');
    }

    public function testGetIpInformationRejectsPrivateIp()
    {
        $this->expectException(\InvalidArgumentException::class);
        UserInfo::getIpInformation('192.168.1.1'); // no network call made
    }

    public function testGetAllInfoPropagatesIpLookupFailure()
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getName')->willReturn('mock-provider');
        $provider->method('fetch')->willThrowException(new \RuntimeException('all providers failed'));

        UserInfo::setOrchestrator(new ProviderOrchestrator([$provider]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('all providers failed');
        UserInfo::getAllInfo();
    }

    // --- user-agent parsing -------------------------------------------------

    public function testDeviceDetection()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) Mobile/15E148';
        $this->assertSame('Mobile', UserInfo::getDevice());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X)';
        $this->assertSame('Tablet', UserInfo::getDevice());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $this->assertSame('Desktop', UserInfo::getDevice());
    }

    public function testBrowserDetectionPrefersSpecific()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML) Chrome/120 Safari/537.36 Edg/120';
        $this->assertSame('Edge', UserInfo::getBrowser());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120 Safari/537.36';
        $this->assertSame('Chrome', UserInfo::getBrowser());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605 Version/16 Safari/605';
        $this->assertSame('Safari', UserInfo::getBrowser());
    }

    public function testOperatingSystemDetection()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $this->assertSame('Windows', UserInfo::getOperatingSystem());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 13)';
        $this->assertSame('Android', UserInfo::getOperatingSystem());
    }

    // --- misc getters are crash-safe ----------------------------------------

    public function testRequestTimeFallsBackWhenUnset()
    {
        unset($_SERVER['REQUEST_TIME']);
        $this->assertIsInt(UserInfo::getRequestTime());
    }

    public function testProtocolDetection()
    {
        unset($_SERVER['HTTPS']);
        $this->assertSame('HTTP', UserInfo::getProtocol());
        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('HTTPS', UserInfo::getProtocol());
    }
}

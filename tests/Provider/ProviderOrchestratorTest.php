<?php

namespace JarirAhmed\UserInfo\Tests\Provider;

use JarirAhmed\UserInfo\Orchestration\ProviderOrchestrator;
use JarirAhmed\UserInfo\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;

class ProviderOrchestratorTest extends TestCase
{
    public function testReturnsFirstSuccessfulProvider()
    {
        $failProvider = $this->createMock(ProviderInterface::class);
        $failProvider->method('getName')->willReturn('failing');
        $failProvider->method('fetch')->willThrowException(new \RuntimeException('fail'));

        $okProvider = $this->createMock(ProviderInterface::class);
        $okProvider->method('getName')->willReturn('ok');
        $okProvider->method('fetch')->willReturn(['query' => '1.2.3.4', '_provider' => 'ok']);

        $orch = new ProviderOrchestrator([$failProvider, $okProvider]);
        $result = $orch->fetch('1.2.3.4');

        $this->assertSame('ok', $result['_provider']);
    }

    public function testThrowsWhenAllProvidersFail()
    {
        $p1 = $this->createMock(ProviderInterface::class);
        $p1->method('getName')->willReturn('a');
        $p1->method('fetch')->willThrowException(new \RuntimeException('a fail'));

        $p2 = $this->createMock(ProviderInterface::class);
        $p2->method('getName')->willReturn('b');
        $p2->method('fetch')->willThrowException(new \RuntimeException('b fail'));

        $orch = new ProviderOrchestrator([$p1, $p2]);

        $this->expectException(\RuntimeException::class);
        $orch->fetch('1.2.3.4');
    }

    public function testGetProviderNamesReturnsAllNames()
    {
        $p = $this->createMock(ProviderInterface::class);
        $p->method('getName')->willReturn('x');

        $orch = new ProviderOrchestrator([$p, $p, $p]);
        $this->assertSame(['x', 'x', 'x'], $orch->getProviderNames());
    }
}

<?php

namespace JarirAhmed\UserInfo\Orchestration;

use JarirAhmed\UserInfo\Provider\ProviderInterface;

class ProviderOrchestrator
{
    /** @var ProviderInterface[] */
    private $providers;

    /**
     * @param ProviderInterface[] $providers Ordered providers (highest priority first)
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Try providers in order; return first success.
     *
     * @param string $ip
     * @return array
     * @throws \RuntimeException if all providers fail
     */
    public function fetch(string $ip): array
    {
        $errors = [];

        foreach ($this->providers as $provider) {
            try {
                return $provider->fetch($ip);
            } catch (\Throwable $e) {
                $errors[$provider->getName()] = $e->getMessage();
            }
        }

        $detail = implode('; ', array_map(
            function ($name, $msg) {
                return "{$name}: {$msg}";
            },
            array_keys($errors),
            $errors
        ));

        throw new \RuntimeException("All IP info providers failed. Details: {$detail}");
    }

    /**
     * Return configured provider names.
     *
     * @return string[]
     */
    public function getProviderNames(): array
    {
        return array_map(function (ProviderInterface $p) {
            return $p->getName();
        }, $this->providers);
    }
}

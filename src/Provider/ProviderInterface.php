<?php

namespace JarirAhmed\UserInfo\Provider;

interface ProviderInterface
{
    /**
     * Human-readable provider name (for debugging/metadata).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Fetch normalized IP information for the given publicly routable IP.
     *
     * @param string $ip
     * @return array Normalized IP info
     * @throws \RuntimeException on any failure
     */
    public function fetch(string $ip): array;
}

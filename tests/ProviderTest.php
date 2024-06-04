<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests;

class ProviderTest extends BaseTest
{
    /**
     * @return void
     */
    public function testIsTestnet(): void
    {
        $this->assertTrue($this->provider->network->isTestnet());
    }

    /**
     * @return void
     */
    public function testRpcConnection(): void
    {
        $this->assertTrue($this->provider->checkRpcConnection());
    }

    /**
     * @return void
     */
    public function testWsConnection(): void
    {
        $this->assertTrue($this->provider->checkWsConnection());
    }
}

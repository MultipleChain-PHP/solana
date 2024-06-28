<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Solana\Provider;
use MultipleChain\SolanaSDK\PublicKey;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\ContractInterface;

class Contract implements ContractInterface
{
    /**
     * @var string
     */
    private string $address;

    /**
     * @var PublicKey
     */
    protected PublicKey $pubKey;

    /**
     * @var array<string,mixed>
     */
    private array $cachedMethods = [];

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @param string $address
     * @param Provider|null $provider
     */
    public function __construct(string $address, ?ProviderInterface $provider = null)
    {
        $this->address = $address;
        $this->pubKey = new PublicKey($address);
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethod(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethodWithCache(string $method, mixed ...$args): mixed
    {
        if (isset($this->cachedMethods[$method])) {
            return $this->cachedMethods[$method];
        }

        return $this->cachedMethods[$method] = $this->callMethod($method, ...$args);
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function getMethodData(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param string $from
     * @param mixed ...$args
     * @return mixed
     */
    public function createTransactionData(string $method, string $from, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }
}

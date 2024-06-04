<?php

declare(strict_types=1);

namespace MultipleChain\Solana;

use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\BaseNetworkConfig as NetworkConfig;

class Provider implements ProviderInterface
{
    /**
     * @var NetworkConfig
     */
    public NetworkConfig $network;

    /**
     * @var Provider|null
     */
    private static ?Provider $instance;

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->update($network);
    }

    /**
     * @return Provider
     */
    public static function instance(): Provider
    {
        if (null === self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_NOT_INITIALIZED->value);
        }
        return self::$instance;
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public static function initialize(array $network): void
    {
        if (null !== self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_ALREADY_INITIALIZED->value);
        }
        self::$instance = new self($network);
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public function update(array $network): void
    {
        self::$instance = $this;
        $this->network = new NetworkConfig($network);
    }

    /**
     * @return bool
     */
    public function isTestnet(): bool
    {
        return $this->network->isTestnet();
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkRpcConnection(?string $url = null): bool
    {
        return true;
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkWsConnection(?string $url = null): bool
    {
        return true;
    }
}

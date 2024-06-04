<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Solana\Provider;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\Solana\Services\TransactionSigner;

class Coin implements CoinInterface
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param Provider|null $provider
     */
    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Coin';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'COIN';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 18;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $this->provider->isTestnet(); // just for phpstan
        return new Number(0);
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        return new TransactionSigner('example');
    }
}

<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Services;

use MultipleChain\Solana\Provider;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var mixed
     */
    private mixed $rawData;

    /**
     * @var mixed
     */
    private mixed $signedData;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param mixed $rawData
     * @param Provider|null $provider
     * @return void
     */
    public function __construct(mixed $rawData, ?ProviderInterface $provider = null)
    {
        $this->rawData = $rawData;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @param string $privateKey
     * @return TransactionSignerInterface
     */
    public function sign(string $privateKey): TransactionSignerInterface
    {
        // example implementation
        $this->provider->isTestnet(); // just for phpstan
        $this->signedData = 'signedData';
        return $this;
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        // example implementation
        return 'transactionId';
    }

    /**
     * @return mixed
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * @return mixed
     */
    public function getSignedData(): mixed
    {
        return $this->signedData;
    }
}

<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Services;

use MultipleChain\Solana\Provider;
use MultipleChain\SolanaSDK\Keypair;
use MultipleChain\SolanaSDK\Transaction;
use MultipleChain\SolanaSDK\Util\Commitment;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var Transaction
     */
    private Transaction $rawData;

    /**
     * @var string
     */
    private string $signedData;

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
        $this->rawData->setRecentBlockhash(
            $this->provider->web3->getLatestBlockhash(
                Commitment::finalized()
            )['blockhash']
        );

        $this->rawData->sign(Keypair::fromPrivateKey($privateKey));

        $this->signedData = $this->rawData->serialize(false, true);

        return $this;
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        return $this->provider->web3->sendRawTransaction($this->signedData);
    }

    /**
     * @return Transaction
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * @return string
     */
    public function getSignedData(): mixed
    {
        return $this->signedData;
    }
}

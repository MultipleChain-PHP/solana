<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Utils;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Solana\Provider;
use MultipleChain\SolanaSDK\PublicKey;
use MultipleChain\SolanaSDK\Transaction;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\SolanaSDK\Programs\SystemProgram;
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
        return 'Solana';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'SOL';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 9;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        return new Number(Utils::fromLamports($this->provider->web3->getBalance($owner)), $this->getDecimals());
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($sender)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $lamports = Utils::toLamports($amount);
        $senderPubKey = new PublicKey($sender);
        $receiverPubKey = new PublicKey($receiver);

        $transaction = (new Transaction())->add(
            SystemProgram::transfer($senderPubKey, $receiverPubKey, $lamports)
        )
        ->setFeePayer($senderPubKey);

        return new TransactionSigner($transaction);
    }
}

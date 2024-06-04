<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\Assets\TokenInterface;
use MultipleChain\Solana\Services\TransactionSigner;

class Token extends Contract implements TokenInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Token';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'TOKEN';
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
        return new Number('0');
    }

    /**
     * @return Number
     */
    public function getTotalSupply(): Number
    {
        return new Number('0');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @return Number
     */
    public function getAllowance(string $owner, string $spender): Number
    {
        return new Number('0');
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

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        float $amount
    ): TransactionSigner {
        return new TransactionSigner('example');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param float $amount
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, float $amount): TransactionSigner
    {
        return new TransactionSigner('example');
    }
}

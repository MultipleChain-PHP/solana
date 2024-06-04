<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\CoinTransactionInterface;

class CoinTransaction extends Transaction implements CoinTransactionInterface
{
    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return '0x';
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return '0x';
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        return new Number('0');
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }
}

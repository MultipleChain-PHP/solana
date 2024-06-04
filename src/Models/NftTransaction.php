<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\NftTransactionInterface;

class NftTransaction extends ContractTransaction implements NftTransactionInterface
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
     * @return string
     */
    public function getNftId(): int|string
    {
        return 0;
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param int|string $nftId
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, int|string $nftId): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }
}

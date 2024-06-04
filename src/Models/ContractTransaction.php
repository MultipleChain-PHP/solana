<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Interfaces\Models\ContractTransactionInterface;

class ContractTransaction extends Transaction implements ContractTransactionInterface
{
    /**
     * @return string
     */
    public function getAddress(): string
    {
        return '0x';
    }
}

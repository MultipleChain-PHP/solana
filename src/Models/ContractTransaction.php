<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\SolanaSDK\Types\ParsedInstruction;
use MultipleChain\SolanaSDK\Types\ParsedTransactionWithMeta;
use MultipleChain\Interfaces\Models\ContractTransactionInterface;

class ContractTransaction extends Transaction implements ContractTransactionInterface
{
    /**
     * @param ParsedTransactionWithMeta $data
     * @return ParsedInstruction|null
     */
    private function findTransferInstruction(ParsedTransactionWithMeta $data): ?ParsedInstruction
    {
        $length = count($data->getTransaction()->getMessage()->getInstructions());
        return $data->getTransaction()->getMessage()->getInstructions()[$length - 1];
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();

        if (null === $data) {
            return '';
        }

        $instruction = $this->findTransferInstruction($data);

        if (null === $instruction) {
            return '';
        }

        return $instruction->getProgramId()->toBase58();
    }
}

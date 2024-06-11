<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Utils;
use MultipleChain\Solana\Assets\Coin;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\SolanaSDK\Types\ParsedInstruction;
use MultipleChain\SolanaSDK\Types\ParsedTransactionWithMeta;
use MultipleChain\Interfaces\Models\CoinTransactionInterface;

class CoinTransaction extends Transaction implements CoinTransactionInterface
{
    /**
     * @param ParsedTransactionWithMeta $data
     * @return ParsedInstruction|null
     */
    private function findTransferInstruction(ParsedTransactionWithMeta $data): ?ParsedInstruction
    {
        return array_reduce($data->getTransaction()->getMessage()->getInstructions(), function ($carry, $instruction) {
            /**
             * @var ParsedInstruction $instruction
             */
            if (
                null !== $instruction->getParsed()
                && in_array($instruction->getParsed()['type'], ['transfer', 'createAccount'])
            ) {
                return $instruction;
            }
            return $carry;
        }, null);
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $data = $this->getData();

        if (null === $data) {
            return '';
        }

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return '';
        }

        return $parsed['info']['destination'] ?? $parsed['info']['newAccount'] ?? '';
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        $data = $this->getData();

        if (null === $data) {
            return '';
        }

        return $this->findTransferInstruction($data)?->getParsed()['info']['source'] ?? '';
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        $data = $this->getData();

        if (null === $data) {
            return new Number(0);
        }

        $instruction = $this->findTransferInstruction($data);

        if (null === $instruction) {
            return new Number(0);
        }

        $lamports = $instruction->getParsed()['info']['lamports'];

        return new Number(Utils::fromLamports($lamports), (new Coin())->getDecimals());
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getAmount()->toFloat() !== $amount) {
            return TransactionStatus::FAILED;
        }

        if (AssetDirection::INCOMING === $direction) {
            if (strtolower($this->getReceiver()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        } else {
            if (strtolower($this->getSender()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        }

        return TransactionStatus::CONFIRMED;
    }
}

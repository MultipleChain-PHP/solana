<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\SolanaSDK\Types\ParsedInstruction;
use MultipleChain\SolanaSDK\Types\ParsedTransactionWithMeta;
use MultipleChain\Interfaces\Models\NftTransactionInterface;

class NftTransaction extends ContractTransaction implements NftTransactionInterface
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
                && in_array($instruction->getParsed()['type'], ['transfer', 'transferChecked'])
            ) {
                return $instruction;
            }
            return $carry;
        }, null);
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

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return '';
        }

        if (isset($parsed['info']['mint'])) {
            return $parsed['info']['mint'];
        }

        $postBalance = array_reduce($data->getMeta()?->getPostTokenBalances() ?? [], function ($carry, $balance) {
            $balance = $balance->toArray();
            if (isset($balance['mint'])) {
                return $balance;
            }
            return $carry;
        }, null);

        if (null !== $postBalance) {
            return $postBalance['mint'];
        }

        return parent::getAddress();
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

        $balances = $data->getMeta()?->getPostTokenBalances() ?? [];

        if (0 === count($balances)) {
            return '';
        }

        return $balances[0]->getOwner() ?? '';
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

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return '';
        }

        return $parsed['info']['authority'];
    }

    /**
     * @return string
     */
    public function getNftId(): int|string
    {
        $data = $this->getData();

        if (null === $data) {
            return '';
        }

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return '';
        }

        return $parsed['info']['mint'];
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param int|string $nftId
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, int|string $nftId): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getNftId() !== $nftId) {
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

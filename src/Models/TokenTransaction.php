<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Utils\Math;
use MultipleChain\Utils\Number;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\SolanaSDK\Types\ParsedInstruction;
use MultipleChain\SolanaSDK\Types\ParsedTransactionWithMeta;
use MultipleChain\Interfaces\Models\TokenTransactionInterface;

class TokenTransaction extends ContractTransaction implements TokenTransactionInterface
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

        $postBalance = array_reduce($data->getMeta()->getPostTokenBalances(), function ($carry, $balance) {
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

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return '';
        }

        $accountInfo = $this->provider->web3->getParsedAccountInfo($parsed['info']['destination']);

        if (null === $accountInfo) {
            return '';
        }

        return $accountInfo->getData()->getParsed()['info']['owner'];
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
     * @return Number
     */
    public function getAmount(): Number
    {
        $data = $this->getData();

        if (null === $data) {
            return new Number(0);
        }

        $parsed = $this->findTransferInstruction($data)?->getParsed();

        if (null === $parsed) {
            return new Number(0);
        }

        if (isset($parsed['info']['tokenAmount']['uiAmount'])) {
            return new Number(
                $parsed['info']['tokenAmount']['uiAmount'],
                $parsed['info']['tokenAmount']['decimals']
            );
        }

        $amount = $parsed['info']['amount'];

        $postBalance = array_reduce($data->getMeta()->getPostTokenBalances(), function ($carry, $balance) {
            if (isset($balance['mint'])) {
                return $balance;
            }
            return $carry;
        }, null);

        $decimals = $postBalance['uiTokenAmount']['decimals'] ?? 0;

        return new Number(Math::div($amount, Math::pow(10, $decimals, $decimals)), $decimals);
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

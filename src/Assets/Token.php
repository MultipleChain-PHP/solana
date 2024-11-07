<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\SolanaSDK\PublicKey;
use MultipleChain\SolanaSDK\Transaction;
use MultipleChain\SolanaSDK\Util\Commitment;
use MultipleChain\Interfaces\Assets\TokenInterface;
use MultipleChain\Solana\Services\TransactionSigner;
use MultipleChain\SolanaSDK\Programs\SplTokenProgram;

class Token extends Contract implements TokenInterface
{
    /**
     * @var array<mixed>
     */
    private array $metadata = [];

    /**
     * @return array<mixed>
     */
    public function getMetadata(): array
    {
        if (!empty($this->metadata)) {
            return $this->metadata;
        }

        $accountInfo = $this->provider->web3->getParsedAccountInfo($this->getAddress());

        if (!$accountInfo) {
            return [];
        }

        return $this->metadata = SplTokenProgram::getTokenMetadata(
            $this->provider->web3,
            $this->pubKey,
            $accountInfo->getOwner()
        ) ?? [];
    }

    /**
     * @return PublicKey
     */
    public function getProgramId(): PublicKey
    {
        $accountInfo = $this->provider->web3->getParsedAccountInfo($this->getAddress());

        if (!$accountInfo) {
            return new PublicKey(SplTokenProgram::SOLANA_TOKEN_PROGRAM);
        }

        return $accountInfo->getOwner();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $this->getMetadata();
        return $this->metadata['name'] ?? '';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        $this->getMetadata();
        return $this->metadata['symbol'] ?? '';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        $this->getMetadata();
        return $this->metadata['decimals'] ?? 0;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        try {
            $res = $this->provider->web3->getParsedTokenAccountsByOwner(
                $owner,
                [
                    'mint' => $this->getAddress()
                ],
                Commitment::confirmed()
            );

            if (!isset($res[0])) {
                return new Number(0);
            }

            return new Number(
                $res[0]->getAccount()->getData()->getParsed()['info']['tokenAmount']['uiAmount'] ?? 0,
                $this->getDecimals()
            );
        } catch (\Exception $e) {
            return new Number(0);
        }
    }

    /**
     * @return Number
     */
    public function getTotalSupply(): Number
    {
        return new Number($this->provider->web3->getTokenSupply($this->getAddress())['uiAmount'], $this->getDecimals());
    }

    /**
     * @param string $owner
     * @param string|null $spender
     * @return Number
     */
    public function getAllowance(string $owner, ?string $spender = null): Number
    {
        try {
            $ownerResult = $this->provider->web3->getParsedTokenAccountsByOwner(
                $owner,
                [
                    'mint' => $this->getAddress()
                ],
                Commitment::confirmed()
            );

            if (!isset($ownerResult[0])) {
                return new Number(0);
            }

            $parsed = $ownerResult[0]->getAccount()->getData()->getParsed();

            if (!isset($parsed['info']['delegatedAmount'])) {
                return new Number(0);
            }

            if ($spender) {
                if (
                    strtolower($parsed['info']['delegate']) !== strtolower($spender)
                ) {
                    return new Number(0);
                }
            }

            return new Number($parsed['info']['delegatedAmount']['uiAmount'], $this->getDecimals());
        } catch (\Exception $e) {
            return new Number(0);
        }
    }

    /**
     * @param float $amount
     * @return int
     */
    private function fromAmount(float $amount): int
    {
        return (int) ($amount * (10 ** $this->getDecimals()));
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        return $this->transferFrom($sender, $sender, $receiver, $amount);
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
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($owner)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        if (strtolower($spender) !== strtolower($owner)) {
            $allowance = $this->getAllowance($owner, $spender);

            if (0 == $allowance->toFloat()) {
                throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
            }

            if ($amount > $allowance->toFloat()) {
                throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
            }
        }

        $transaction = new Transaction();
        $programId = $this->getProgramId();
        $ownerPubKey = new PublicKey($owner);
        $spenderPubKey = new PublicKey($spender);
        $receiverPubKey = new PublicKey($receiver);
        $transferAmount = $this->fromAmount($amount);

        $ownerAccount = $this->getTokenAccount($ownerPubKey, $programId);
        $receiverAccount = $this->getTokenAccount($receiverPubKey, $programId);

        if (!$this->provider->web3->getParsedAccountInfo($receiverAccount->toString())) {
            $transaction->add(
                SplTokenProgram::createAssociatedTokenAccountInstruction(
                    $spenderPubKey,
                    $receiverAccount,
                    $receiverPubKey,
                    $this->pubKey,
                    $programId,
                )
            );
        }

        $transaction->add(
            SplTokenProgram::createTransferInstruction(
                $ownerAccount,
                $receiverAccount,
                $spenderPubKey,
                $transferAmount,
                [],
                $programId
            )
        );

        $transaction->setFeePayer($spenderPubKey);

        return new TransactionSigner($transaction);
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param float $amount
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($owner)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $transaction = new Transaction();
        $programId = $this->getProgramId();
        $ownerPubKey = new PublicKey($owner);
        $spenderPubKey = new PublicKey($spender);
        $approveAmount = $this->fromAmount($amount);

        $ownerAccount = $this->getTokenAccount($ownerPubKey, $programId);

        $transaction->add(
            SplTokenProgram::createApproveInstruction(
                $ownerAccount,
                $spenderPubKey,
                $ownerPubKey,
                $approveAmount,
                [],
                $programId
            )
        );

        $transaction->setFeePayer($ownerPubKey);

        return new TransactionSigner($transaction);
    }
}

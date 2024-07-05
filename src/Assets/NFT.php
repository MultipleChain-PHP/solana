<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\SolanaSDK\PublicKey;
use MultipleChain\SolanaSDK\Transaction;
use MultipleChain\Interfaces\Assets\NftInterface;
use MultipleChain\Solana\Services\TransactionSigner;
use MultipleChain\SolanaSDK\Programs\SplTokenProgram;

class NFT extends Contract implements NftInterface
{
    /**
     * @var array<mixed>
     */
    private array $metadata = [];

    /**
     * @param PublicKey|null $pubKey
     * @return array<mixed>
     */
    public function getMetadata(?PublicKey $pubKey = null): array
    {
        try {
            if (!empty($this->metadata) && null === $pubKey) {
                return $this->metadata;
            }

            return $this->metadata = SplTokenProgram::getTokenMetadata(
                $this->provider->web3,
                $pubKey ?? $this->pubKey,
                new PublicKey(SplTokenProgram::SOLANA_TOKEN_PROGRAM)
            );
        } catch (\Exception $e) {
            return [];
        }
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
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        // This is not stable way to get NFT balance
        $accounts = $this->provider->web3->getParsedTokenAccountsByOwner($owner, [
            'programId' => SplTokenProgram::SOLANA_TOKEN_PROGRAM,
        ]);

        $nftAccounts = [];

        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $parsed = $account->getAccount()->getData()->getParsed();
                $decimals = $parsed['info']['tokenAmount']['decimals'];
                if (0 == $decimals) {
                    $nftAccounts[] = [
                        'mint' => new PublicKey($parsed['info']['mint']),
                        'owner' => new PublicKey($parsed['info']['owner'])
                    ];
                }
            }
        }

        return new Number(count($nftAccounts) - 1, 0);
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getOwner(int|string $tokenId): string
    {
        $accounts = $this->provider->web3->getTokenLargestAccounts($tokenId);
        $accountInfo = $this->provider->web3->getParsedAccountInfo($accounts[0]['address']);
        return $accountInfo->getData()->getParsed()['info']['owner'];
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getTokenURI(int|string $tokenId): string
    {
        $this->getMetadata(new PublicKey($tokenId));
        return $this->metadata['uri'] ?? '';
    }

    /**
     * @param int|string $tokenId
     * @return string|null
     */
    public function getApproved(int|string $tokenId): ?string
    {
        $accounts = $this->provider->web3->getTokenLargestAccounts($tokenId);
        $accountInfo = $this->provider->web3->getParsedAccountInfo($accounts[0]['address']);
        return $accountInfo->getData()->getParsed()['info']['delegate'] ?? null;
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, int|string $tokenId): TransactionSigner
    {
        return $this->transferFrom($sender, $sender, $receiver, $tokenId);
    }

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        int|string $tokenId
    ): TransactionSigner {
        if ($this->getBalance($owner)->toFloat() <= 0) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $originalOwner = $this->getOwner($tokenId);
        if ($originalOwner !== $owner) {
            throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
        }

        if (strtolower($spender) !== strtolower($owner)) {
            if (strtolower($this->getApproved($tokenId) ?? '') !== strtolower($spender)) {
                throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
            }
        }

        $transaction = new Transaction();
        $nftPubKey = new PublicKey($tokenId);
        $ownerPubKey = new PublicKey($owner);
        $spenderPubKey = new PublicKey($spender);
        $receiverPubKey = new PublicKey($receiver);
        $programId = $this->getProgramId($nftPubKey);

        $ownerAccount = SplTokenProgram::getAssociatedTokenAddress(
            $nftPubKey,
            $ownerPubKey,
            false,
            $programId
        );

        $receiverAccount = SplTokenProgram::getAssociatedTokenAddress(
            $nftPubKey,
            $receiverPubKey,
            false,
            $programId
        );

        if (!$this->provider->web3->getParsedAccountInfo($receiverAccount->toString())) {
            $transaction->add(
                SplTokenProgram::createAssociatedTokenAccountInstruction(
                    $spenderPubKey,
                    $receiverAccount,
                    $receiverPubKey,
                    $nftPubKey,
                    $programId,
                )
            );
        }

        $transaction->add(
            SplTokenProgram::createTransferInstruction(
                $ownerAccount,
                $receiverAccount,
                $spenderPubKey,
                1,
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
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, int|string $tokenId): TransactionSigner
    {
        if ($this->getBalance($owner)->toFloat() <= 0) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $originalOwner = $this->getOwner($tokenId);
        if ($originalOwner !== $owner) {
            throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
        }

        $transaction = new Transaction();
        $nftPubKey = new PublicKey($tokenId);
        $ownerPubKey = new PublicKey($owner);
        $spenderPubKey = new PublicKey($spender);
        $programId = $this->getProgramId($nftPubKey);

        $ownerAccount = SplTokenProgram::getAssociatedTokenAddress(
            $nftPubKey,
            $ownerPubKey,
            false,
            $programId
        );

        $transaction->add(
            SplTokenProgram::createApproveInstruction(
                $ownerAccount,
                $spenderPubKey,
                $ownerPubKey,
                1,
                [],
                $programId
            )
        );

        $transaction->setFeePayer($ownerPubKey);

        return new TransactionSigner($transaction);
    }
}

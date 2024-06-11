<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Solana\Tests\BaseTest;
use MultipleChain\Solana\Models\TokenTransaction;

class TokenTransactionTest extends BaseTest
{
    /**
     * @var TokenTransaction
     */
    private TokenTransaction $tx;

    /**
     * @var TokenTransaction
     */
    private TokenTransaction $tx2022;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new TokenTransaction($this->data->tokenTransferTx);
        $this->tx2022 = new TokenTransaction($this->data->token2022TransferTx);
    }

    /**
     * @return void
     */
    public function testReceiver(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getReceiver()),
            strtolower($this->data->modelTestReceiver)
        );
        $this->assertEquals(
            strtolower($this->tx2022->getReceiver()),
            strtolower($this->data->modelTestReceiver)
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getSender()),
            strtolower($this->data->modelTestSender)
        );
        $this->assertEquals(
            strtolower($this->tx2022->getSender()),
            strtolower($this->data->modelTestSender)
        );
    }

    /**
     * @return void
     */
    public function testProgram(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getAddress()),
            strtolower($this->data->tokenTestAddress)
        );
        $this->assertEquals(
            strtolower($this->tx2022->getAddress()),
            strtolower($this->data->token2022TestAddress)
        );
    }

    /**
     * @return void
     */
    public function testAmount(): void
    {
        $this->assertEquals(
            $this->tx->getAmount()->toFloat(),
            $this->data->tokenAmount
        );
        $this->assertEquals(
            $this->tx2022->getAmount()->toFloat(),
            $this->data->tokenAmount
        );
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(
            $this->tx->getType(),
            TransactionType::TOKEN
        );
        $this->assertEquals(
            $this->tx2022->getType(),
            TransactionType::TOKEN
        );
    }

    /**
     * @return void
     */
    public function testVerifyTransfer(): void
    {
        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestReceiver,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::FAILED
        );

        $this->assertEquals(
            $this->tx2022->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestReceiver,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx2022->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx2022->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::FAILED
        );
    }
}

<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Solana\Tests\BaseTest;
use MultipleChain\Solana\Models\CoinTransaction;

class CoinTransactionTest extends BaseTest
{
    /**
     * @var CoinTransaction
     */
    private CoinTransaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new CoinTransaction($this->data->coinTransferTx);
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
    }

    /**
     * @return void
     */
    public function testAmount(): void
    {
        $this->assertEquals(
            $this->tx->getAmount()->toString(),
            (string) $this->data->coinAmount
        );
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(
            $this->tx->getType(),
            TransactionType::COIN
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
                $this->data->coinAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->modelTestSender,
                $this->data->coinAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestSender,
                $this->data->coinAmount
            ),
            TransactionStatus::FAILED
        );
    }
}

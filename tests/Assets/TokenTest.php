<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Assets\Token;
use MultipleChain\Solana\Tests\BaseTest;
use MultipleChain\Solana\Models\Transaction;

class TokenTest extends BaseTest
{
    /**
     * @var Token
     */
    private Token $token;

    /**
     * @var Token
     */
    private Token $token2022;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->token = new Token($this->data->tokenTestAddress);
        $this->token2022 = new Token($this->data->token2022TestAddress);
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('Example', $this->token->getName());
        $this->assertEquals('Example Token 2022', $this->token2022->getName());
    }

    /**
     * @return void
     */
    public function testSymbol(): void
    {
        $this->assertEquals('EXM', $this->token->getSymbol());
        $this->assertEquals('EXM2', $this->token2022->getSymbol());
    }

    /**
     * @return void
     */
    public function testDecimals(): void
    {
        $this->assertEquals(8, $this->token->getDecimals());
        $this->assertEquals(9, $this->token2022->getDecimals());
    }

    /**
     * @return void
     */
    public function testBalance(): void
    {
        $this->assertEquals(
            $this->data->tokenBalanceTestAmount,
            $this->token->getBalance($this->data->balanceTestAddress)->toFloat()
        );
        $this->assertEquals(
            $this->data->tokenBalanceTestAmount,
            $this->token2022->getBalance($this->data->balanceTestAddress)->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testTotalSupply(): void
    {
        $this->assertEquals(
            100000000000,
            $this->token->getTotalSupply()->toFloat()
        );
        $this->assertEquals(
            10000000,
            $this->token2022->getTotalSupply()->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        $signer = $this->token->transfer(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->tokenTransferTestAmount
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->tokenTransferTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $beforeBalance = $this->token->getBalance($this->data->receiverTestAddress);

        (new Transaction($signer->send()))->wait();

        $afterBalance = $this->token->getBalance($this->data->receiverTestAddress);

        $transferNumber = new Number($this->data->tokenTransferTestAmount, $this->token->getDecimals());

        $this->assertEquals(
            $afterBalance->toString(),
            $beforeBalance->add($transferNumber)->toString()
        );
    }

    /**
     * @return void
     */
    public function testTransfer2022(): void
    {
        $signer = $this->token2022->transfer(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->tokenTransferTestAmount
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->tokenTransferTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $beforeBalance = $this->token2022->getBalance($this->data->receiverTestAddress);

        (new Transaction($signer->send()))->wait();

        $afterBalance = $this->token2022->getBalance($this->data->receiverTestAddress);

        $transferNumber = new Number($this->data->tokenTransferTestAmount, $this->token2022->getDecimals());

        $this->assertEquals(
            $afterBalance->toString(),
            $beforeBalance->add($transferNumber)->toString()
        );
    }

    /**
     * @return void
     */
    public function testApproveAndAllowance(): void
    {
        $signer = $this->token->approve(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->tokenApproveTestAmount
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->tokenApproveTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        (new Transaction($signer->send()))->wait();

        $allowance = $this->token->getAllowance(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress
        );

        $this->assertEquals(
            $this->data->tokenApproveTestAmount,
            $allowance->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testApproveAndAllowance2022(): void
    {
        $signer = $this->token2022->approve(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->tokenApproveTestAmount
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->tokenApproveTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        (new Transaction($signer->send()))->wait();

        $allowance = $this->token2022->getAllowance(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress
        );

        $this->assertEquals(
            $this->data->tokenApproveTestAmount,
            $allowance->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testTransferFrom(): void
    {
        $signer = $this->token->transferFrom(
            $this->data->receiverTestAddress,
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            2
        );

        $signer = $signer->sign($this->data->receiverPrivateKey);

        if (!$this->data->tokenTransferFromTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $beforeBalance = $this->token->getBalance($this->data->receiverTestAddress);

        (new Transaction($signer->send()))->wait();

        $afterBalance = $this->token->getBalance($this->data->receiverTestAddress);

        $this->assertEquals(
            $afterBalance->toString(),
            $beforeBalance->add(new Number(2))->toString()
        );
    }

    /**
     * @return void
     */
    public function testTransferFrom2022(): void
    {
        $signer = $this->token2022->transferFrom(
            $this->data->receiverTestAddress,
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            2
        );

        $signer = $signer->sign($this->data->receiverPrivateKey);

        if (!$this->data->tokenTransferFromTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $beforeBalance = $this->token2022->getBalance($this->data->receiverTestAddress);

        (new Transaction($signer->send()))->wait();

        $afterBalance = $this->token2022->getBalance($this->data->receiverTestAddress);

        $this->assertEquals(
            $afterBalance->toString(),
            $beforeBalance->add(new Number(2))->toString()
        );
    }
}

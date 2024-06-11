<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests\Models;

use MultipleChain\Solana\Tests\BaseTest;
use MultipleChain\Solana\Models\ContractTransaction;

class ContractTransactionTest extends BaseTest
{
    /**
     * @var ContractTransaction
     */
    private ContractTransaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new ContractTransaction(
            "5pak57tjpTf4BfHweZryxtmJBWsJjeaU56N6CbuwuSuNyPtHwKsu6CZp6Y2L9dHqNJH33w6V895ZQLgRjANJJSR3"
        );
    }

    /**
     * @return void
     */
    public function testReceiver(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getAddress()),
            strtolower("HeXZiyduAmAaYABvrh4bU94TdzB2TkwFuNXfgi1PYFwS")
        );
    }
}

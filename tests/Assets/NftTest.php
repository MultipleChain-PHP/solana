<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Tests\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Assets\NFT;
use MultipleChain\Solana\Tests\BaseTest;
use MultipleChain\Solana\Models\Transaction;

class NftTest extends BaseTest
{
    /**
     * @var NFT
     */
    private NFT $nft;

    /**
     * @var string
     */
    private string $id;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nft = new NFT($this->data->nftTestAddress);
        $this->id = 'F8kj1xPSG69amgDS7XfmkHgAAWgiJ391NFTkxJL2e5Di';
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('Test NFT Collection', $this->nft->getName());
    }

    /**
     * @return void
     */
    public function testSymbol(): void
    {
        $this->assertEquals('TNFT', $this->nft->getSymbol());
    }

    /**
     * @return void
     */
    public function testBalance(): void
    {
        $this->assertEquals(
            $this->data->nftBalanceTestAmount,
            $this->nft->getBalance($this->data->balanceTestAddress)->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testOwner(): void
    {
        $this->assertEquals(
            strtolower($this->data->balanceTestAddress),
            strtolower($this->nft->getOwner($this->id))
        );
    }

    /**
     * @return void
     */
    public function testTokenURI(): void
    {
        $this->assertEquals(
            'https://arweave.net/8SvLYJ8CgpxzihKD2r-DKRmjPlyxa_WGeuA8ARI0ems',
            $this->nft->getTokenURI($this->id)
        );
    }

    /**
     * @return void
     */
    public function testApproved(): void
    {
        $this->assertEquals(
            null,
            $this->nft->getApproved($this->id)
        );
    }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        $signer = $this->nft->transfer(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->nftTransferId
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->nftTransactionTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        (new Transaction($signer->send()))->wait();

        $this->assertEquals(
            strtolower($this->nft->getOwner($this->data->nftTransferId)),
            strtolower($this->data->receiverTestAddress)
        );
    }

    /**
     * @return void
     */
    public function testApprove(): void
    {
        $customOwner = $this->data->nftTransactionTestIsActive
            ? $this->data->receiverTestAddress
            : $this->data->senderTestAddress;
        $customSpender = $this->data->nftTransactionTestIsActive
            ? $this->data->senderTestAddress
            : $this->data->receiverTestAddress;
        $customPrivateKey = $this->data->nftTransactionTestIsActive
            ? $this->data->receiverPrivateKey
            : $this->data->senderPrivateKey;

        $signer = $this->nft->approve(
            $customOwner,
            $customSpender,
            $this->data->nftTransferId
        );

        $signer = $signer->sign($customPrivateKey);

        if (!$this->data->nftTransactionTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        (new Transaction($signer->send()))->wait();

        $this->assertEquals(
            strtolower($this->nft->getApproved($this->data->nftTransferId)),
            strtolower($this->data->senderTestAddress)
        );
    }

    /**
     * @return void
     */
    public function testTransferFrom(): void
    {
        if (!$this->data->nftTransactionTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $signer = $this->nft->transferFrom(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->senderTestAddress,
            $this->data->nftTransferId
        );

        (new Transaction($signer->sign($this->data->senderPrivateKey)->send()))->wait();

        $this->assertEquals(
            strtolower($this->nft->getOwner($this->data->nftTransferId)),
            strtolower($this->data->senderTestAddress)
        );
    }
}

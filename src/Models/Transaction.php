<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Provider;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\TransactionInterface;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var mixed
     */
    private mixed $data;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param string $id
     * @param Provider|null $provider
     */
    public function __construct(string $id, ?ProviderInterface $provider = null)
    {
        $this->id = $id;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        $this->provider->isTestnet(); // just for phpstan
        $this->data = 'data'; // example implementation
        return $this->data;
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        return TransactionType::GENERAL;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return 'https://example.com';
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        return '0x';
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        return new Number('0');
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        return 0;
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }
}

<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Solana\Utils;
use MultipleChain\Solana\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Solana\Assets\Coin;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\SolanaSDK\Util\Commitment;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\SolanaSDK\Types\TokenBalance;
use MultipleChain\SolanaSDK\Programs\SystemProgram;
use MultipleChain\SolanaSDK\Programs\SplTokenProgram;
use MultipleChain\SolanaSDK\Types\ParsedInstruction;
use MultipleChain\SolanaSDK\Types\ParsedMessageAccount;
use MultipleChain\Interfaces\Models\TransactionInterface;
use MultipleChain\SolanaSDK\Types\ParsedTransactionWithMeta;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var ParsedTransactionWithMeta|null
     */
    private ?ParsedTransactionWithMeta $data = null;

    /**
     * @var Provider
     */
    protected Provider $provider;

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
     * @return ParsedTransactionWithMeta|null
     */
    public function getData(): ?ParsedTransactionWithMeta
    {
        try {
            if (null !== $this->data) {
                return $this->data;
            }

            $data = $this->provider->web3->getParsedTransaction($this->id, Commitment::confirmed());

            if (null === $data) {
                return null;
            }

            return $this->data = $data;
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::RPC_REQUEST_ERROR->value);
        }
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        try {
            $status = $this->getStatus();
            if (TransactionStatus::PENDING != $status) {
                return $status;
            }

            sleep($ms / 1000);

            return $this->wait($ms);
        } catch (\Throwable $th) {
            return TransactionStatus::FAILED;
        }
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        $data = $this->getData();

        if (null === $data) {
            return TransactionType::GENERAL;
        }

        $instructions = $data->getTransaction()->getMessage()->getInstructions();

        /**
         * @var ParsedInstruction $instruction
         */
        foreach ($instructions as $instruction) {
            $programId = $instruction->getProgramId();
            if ($programId->equalsBase58(SplTokenProgram::SOLANA_TOKEN_PROGRAM_2022)) {
                return TransactionType::TOKEN;
            } elseif ($programId->equalsBase58(SplTokenProgram::SOLANA_TOKEN_PROGRAM)) {
                $postBalances = $data->getMeta()->getPostTokenBalances();
                /**
                 * @var TokenBalance $postBalance
                 */
                $postBalance = array_reduce($postBalances, function ($carry, $item) {
                    /**
                     * @var TokenBalance $item
                     */
                    if ('' !== $item->getMint()) {
                        return $item;
                    }
                    return $carry;
                });

                if (0 === $postBalance->getUiTokenAmount()->getDecimals()) {
                    return TransactionType::NFT;
                } else {
                    return TransactionType::TOKEN;
                }
            } elseif (
                $programId->equalsBase58(SystemProgram::programId()->toString()) &&
                in_array($instruction->getParsed()['type'], ['createAccount', 'transfer'])
            ) {
                return TransactionType::COIN;
            }
        }

        return TransactionType::CONTRACT;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        $node = $this->provider->node;
        $transactionUrl = $this->provider->node['explorerUrl'] . 'tx/' . $this->id;
        $transactionUrl .= 'mainnet-beta' !== $node['cluster'] ? '?cluster=' . $node['cluster'] : '';
        return $transactionUrl;
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        $data = $this->getData();
        $keys = $data->getTransaction()->getMessage()->getAccountKeys();
        return array_reduce($keys, function ($carry, $item) {
            /**
             * @var ParsedMessageAccount $item
             */
            if ($item->getSigner()) {
                return $item->getPubkey()->toBase58();
            }
            return $carry;
        }, '');
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        $data = $this->getData();
        return new Number(
            Utils::fromLamports(
                $data->getMeta()->getFee()
            ),
            (new Coin())->getDecimals()
        );
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        return $this->getData()?->getSlot();
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        return $this->getData()?->getBlockTime() ?? 0;
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        $slot = $this->provider->web3->getSlot();
        return $slot - $this->getBlockNumber();
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        $data = $this->getData();

        if (null === $data) {
            return TransactionStatus::PENDING;
        }

        return null !== $data->getMeta()->getErr()
            ? TransactionStatus::FAILED
            : TransactionStatus::CONFIRMED;
    }
}

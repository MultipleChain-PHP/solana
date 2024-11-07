<?php

declare(strict_types=1);

namespace MultipleChain\Solana\Assets;

use MultipleChain\Solana\Provider;
use MultipleChain\SolanaSDK\PublicKey;
use MultipleChain\SolanaSDK\Util\Commitment;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\SolanaSDK\Programs\SplTokenProgram;
use MultipleChain\Interfaces\Assets\ContractInterface;

class Contract implements ContractInterface
{
    /**
     * @var string
     */
    private string $address;

    /**
     * @var PublicKey
     */
    protected PublicKey $pubKey;

    /**
     * @var array<string,mixed>
     */
    private array $cachedMethods = [];

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @param string $address
     * @param Provider|null $provider
     */
    public function __construct(string $address, ?ProviderInterface $provider = null)
    {
        $this->address = $address;
        $this->pubKey = new PublicKey($address);
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethod(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethodWithCache(string $method, mixed ...$args): mixed
    {
        if (isset($this->cachedMethods[$method])) {
            return $this->cachedMethods[$method];
        }

        return $this->cachedMethods[$method] = $this->callMethod($method, ...$args);
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function getMethodData(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param string $from
     * @param mixed ...$args
     * @return mixed
     */
    public function createTransactionData(string $method, string $from, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param PublicKey $ownerPubKey
     * @param PublicKey $programId
     * @return PublicKey
     */
    public function getTokenAccount(PublicKey $ownerPubKey, PublicKey $programId): PublicKey
    {
        $account = null;
        try {
            $res = $this->provider->web3->getParsedTokenAccountsByOwner(
                $ownerPubKey->toBase58(),
                [
                    'mint' => $this->getAddress(),
                    'programId' => $programId->toBase58()
                ],
                Commitment::confirmed()
            );
            if (!isset($res[0])) {
                $account = null;
            } else {
                $account = $res[0]->getPubkey();
            }
        } catch (\Exception $e) {
            $account = null;
        }

        if (null === $account) {
            $account = SplTokenProgram::getAssociatedTokenAddress(
                $this->pubKey,
                $ownerPubKey,
                false,
                $programId
            );
        }

        return $account;
    }
}

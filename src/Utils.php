<?php

declare(strict_types=1);

namespace MultipleChain\Solana;

use MultipleChain\Utils as BaseUtils;

class Utils extends BaseUtils
{
    /**
     * @param float $amount
     * @return int
     */
    public static function toLamports(float $amount): int
    {
        return (int) ($amount * 10 ** 9);
    }

    /**
     * @param int $amount
     * @return float
     */
    public static function fromLamports(int $amount): float
    {
        return (float) ($amount / 10 ** 9);
    }
}

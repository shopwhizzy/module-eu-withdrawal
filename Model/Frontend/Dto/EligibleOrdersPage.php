<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawal\Model\Frontend\Dto;

use Magento\Sales\Api\Data\OrderInterface;

final class EligibleOrdersPage
{
    /**
     * @param OrderInterface[] $orders
     * @param bool $hasMore
     */
    public function __construct(
        public readonly array $orders,
        public readonly bool $hasMore,
    ) {
    }
}

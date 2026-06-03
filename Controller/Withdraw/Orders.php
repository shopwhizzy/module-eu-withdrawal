<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawal\Controller\Withdraw;

use MageMe\EUWithdrawal\Block\Withdraw\OrderSelector;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutInterface;

class Orders implements HttpGetActionInterface
{
    private const ROWS_TEMPLATE = 'MageMe_EUWithdrawal::withdraw/step1b_orders_rows.phtml';

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly LayoutInterface $layout,
        private readonly CustomerSession $customerSession,
    ) {
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['html' => '', 'hasMore' => false]);
        }

        /** @var OrderSelector $block */
        $block = $this->layout->createBlock(OrderSelector::class);
        $block->setTemplate(self::ROWS_TEMPLATE);

        return $result->setData([
            'html' => $block->toHtml(),
            'hasMore' => $block->canLoadMore(),
            'nextHref' => $block->getLoadMoreUrl(),
            'nextEndpoint' => $block->getLoadMoreEndpoint(),
        ]);
    }
}

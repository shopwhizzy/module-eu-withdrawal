<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawal\Block\Withdraw;

use MageMe\EUWithdrawal\Model\Period\DeliveryDateResolver;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;

class OrderMeta extends Template
{
    /**
     * Constructor.
     *
     * @param Context $context
     * @param TimezoneInterface $timezone
     * @param PriceCurrencyInterface $priceCurrency
     * @param DeliveryDateResolver $deliveryDate
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly TimezoneInterface $timezone,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly DeliveryDateResolver $deliveryDate,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get order.
     *
     * @return ?OrderInterface
     */
    public function getOrder(): ?OrderInterface
    {
        $o = $this->getData('order');
        return $o instanceof OrderInterface ? $o : null;
    }

    /**
     * Get order increment id.
     *
     * @return string
     */
    public function getOrderIncrementId(): string
    {
        $o = $this->getOrder();
        return $o !== null ? (string) $o->getIncrementId() : '';
    }

    /**
     * Get placed at.
     *
     * @return string
     */
    public function getPlacedAt(): string
    {
        $o = $this->getOrder();
        return $o !== null ? $this->formatUtc((string) $o->getCreatedAt()) : '';
    }

    /**
     * Get delivered at.
     *
     * @return string
     */
    public function getDeliveredAt(): string
    {
        $o = $this->getOrder();
        if ($o === null) {
            return '';
        }
        $raw = $this->deliveryDate->resolve($o);
        return $raw !== null ? $this->formatUtc($raw) : '';
    }

    /**
     * Is awaiting delivery.
     *
     * @return bool
     */
    public function isAwaitingDelivery(): bool
    {
        $o = $this->getOrder();
        if ($o === null) {
            return false;
        }
        return $this->deliveryDate->resolve($o) === null;
    }

    /**
     * Get shipping method label.
     *
     * @return string
     */
    public function getShippingMethodLabel(): string
    {
        $o = $this->getOrder();
        return $o !== null ? (string) $o->getShippingDescription() : '';
    }

    /**
     * Get grand total formatted.
     *
     * @return string
     */
    public function getGrandTotalFormatted(): string
    {
        $o = $this->getOrder();
        if ($o === null) {
            return '';
        }
        return (string) $this->priceCurrency->format(
            (float) $o->getGrandTotal(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            (string) $o->getOrderCurrencyCode(),
        );
    }

    /**
     * Format utc.
     *
     * @param string $raw
     * @return string
     */
    private function formatUtc(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        try {
            $dt = new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return $raw;
        }
        return (string) $this->timezone->formatDateTime(
            $dt,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::NONE,
        );
    }
}

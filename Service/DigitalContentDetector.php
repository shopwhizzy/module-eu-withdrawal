<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawal\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class DigitalContentDetector
{
    public const CFG_ENABLED = 'mageme_eu_withdrawal/eligibility/digital/enabled';
    public const CFG_TYPES = 'mageme_eu_withdrawal/eligibility/digital/detect_product_types';
    public const CFG_ATTR = 'mageme_eu_withdrawal/eligibility/digital/custom_attribute_code';

    public const STRATEGY_DOWNLOADABLE = 'downloadable';
    public const STRATEGY_VIRTUAL = 'virtual';
    public const STRATEGY_BUNDLE = 'bundle_contains_digital';
    public const STRATEGY_ATTRIBUTE = 'custom_attribute';

    /** @var array<string, ?ProductInterface> */
    private array $productCache = [];

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(self::CFG_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is digital product.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isDigitalProduct(ProductInterface $product): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $strategies = $this->strategies();
        if ($strategies === []) {
            return false;
        }
        $type = (string) $product->getTypeId();
        if (in_array(self::STRATEGY_DOWNLOADABLE, $strategies, true) && $type === 'downloadable') {
            return true;
        }
        if (in_array(self::STRATEGY_VIRTUAL, $strategies, true) && $type === 'virtual') {
            return true;
        }
        if (in_array(self::STRATEGY_ATTRIBUTE, $strategies, true)) {
            $code = (string) ($this->scopeConfig->getValue(self::CFG_ATTR, ScopeInterface::SCOPE_STORE) ?: 'is_digital_content');
            // Quote-item product instances are loaded with a limited attribute
            // set, so getCustomAttribute() returns null for EAV attributes
            // outside the `quote_item_product` attribute group. Reload the
            // full product (cached per request) before checking the flag.
            $value = $product->getCustomAttribute($code)?->getValue()
                ?? $product->getData($code);
            if ($value === null) {
                $full = $this->loadFullProduct($product);
                if ($full !== null) {
                    $value = $full->getCustomAttribute($code)?->getValue()
                        ?? $full->getData($code);
                }
            }
            if ((int) $value === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item> $items
     * @return array<int, \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item>
     */
    public function filterDigitalItems(array $items): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        $out = [];
        foreach ($items as $item) {
            if ($this->isDigitalItem($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Whether a cart/order line counts as digital: its own product, or — when the
     * `bundle_contains_digital` strategy is enabled — any child of a composite line.
     *
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isDigitalItem($item): bool
    {
        $product = $item->getProduct();
        if ($product instanceof ProductInterface && $this->isDigitalProduct($product)) {
            return true;
        }
        if (!in_array(self::STRATEGY_BUNDLE, $this->strategies(), true)) {
            return false;
        }
        foreach ($this->childItems($item) as $child) {
            $childProduct = $child->getProduct();
            if ($childProduct instanceof ProductInterface && $this->isDigitalProduct($childProduct)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Child lines of a composite quote or order line (bundle / configurable).
     *
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @return array<int, mixed>
     */
    private function childItems($item): array
    {
        if (method_exists($item, 'getChildren') && $item->getChildren()) {
            return $item->getChildren();
        }
        if (method_exists($item, 'getChildrenItems') && $item->getChildrenItems()) {
            return $item->getChildrenItems();
        }
        return [];
    }

    /** @return string[] */
    private function strategies(): array
    {
        $raw = (string) ($this->scopeConfig->getValue(self::CFG_TYPES, ScopeInterface::SCOPE_STORE) ?: '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Load full product.
     *
     * @param ProductInterface $partial
     * @return ?ProductInterface
     */
    private function loadFullProduct(ProductInterface $partial): ?ProductInterface
    {
        $sku = (string) $partial->getSku();
        if ($sku === '') {
            return null;
        }
        if (array_key_exists($sku, $this->productCache)) {
            return $this->productCache[$sku];
        }
        try {
            $this->productCache[$sku] = $this->productRepository->get($sku);
        } catch (NoSuchEntityException) {
            $this->productCache[$sku] = null;
        }
        return $this->productCache[$sku];
    }
}

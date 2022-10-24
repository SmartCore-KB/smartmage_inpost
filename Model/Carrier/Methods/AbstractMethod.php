<?php

declare(strict_types=1);

namespace Smartmage\Inpost\Model\Carrier\Methods;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Smartmage\Inpost\Model\ConfigProvider;

abstract class AbstractMethod
{
    /**
     * @var string
     */
    public $methodKey;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var String
     */
    public $carrierCode;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    protected $quoteItems;

    /**
     * @var string
     */
    protected $blockAttribute;

    protected $logger;

    /**
     * AbstractMethod constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PsrLoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAllowed()
    {
        //Check if method is active
        if (!$this->configProvider->getConfigFlag($this->carrierCode . '/' . $this->methodKey . '/active')) {
            return false;
        }

        //Check if products have disabled shipping method type
        if ($this->isShippingDisabled()) {
            return false;
        }

        //Checking that the products do not weigh too much
        $maxWeight = $this->configProvider->getConfigData(
            $this->carrierCode . '/' . $this->methodKey . '/max_cart_weight'
        );
        if ($this->calculateWeight() > $maxWeight) {
            return false;
        }

        if (!$this->isWeekendSendAvailable()) {
            return false;
        }

        return true;
    }

    protected function isWeekendSendAvailable(): bool
    {
        return true;
    }

    protected function calculateWeight()
    {
        $weightAttributeCode = $this->configProvider->getWeightAttributeCode();
        $weight = 0;

        $storeId = $this->storeManager->getStore()->getId();
        foreach ($this->quoteItems as $item) {
            $quoteProduct = $item->getProduct();

            if ($quoteProduct->getTypeId() != Type::TYPE_SIMPLE) {
                continue;
            }

            $productWeight = $quoteProduct->getResource()->getAttributeRawValue(
                (int)$quoteProduct->getId(),
                (string)$weightAttributeCode,
                (int)$storeId
            );

            if (is_array($productWeight)) {
                $productWeight = 0;
            }
            $weight += $productWeight * $item->getQty();
        }

        return $weight;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function isShippingDisabled()
    {
        $storeId = $this->storeManager->getStore()->getId();
        foreach ($this->quoteItems as $item) {
            $product = $item->getProduct();
            $blockShip = $product->getResource()->getAttributeRawValue(
                $product->getId(),
                $this->blockAttribute,
                $storeId
            );

            if ($blockShip) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function calculatePrice($request)
    {
        if ($this->isFreeShipping($request)) {
            return 0;
        }

        return $this->configProvider->getConfigData(
            $this->carrierCode . '/' . $this->methodKey . '/price'
        );
    }

    /**
     * @return mixed
     */
    public function isFreeShipping($request)
    {
        if($request->getFreeShipping()) {
            $this->logger->info(print_r('Method ' . $this->carrierCode . '/' . $this->methodKey . ' free shipping reason: Request free shipping', true));
            return true;
        }

        if ($this->configProvider->getConfigFlag(
            $this->carrierCode . '/' . $this->methodKey . '/free_shipping_enable'
        )) {
            $freeShippingFrom = $this->configProvider->getConfigData(
                $this->carrierCode . '/' . $this->methodKey . '/free_shipping_subtotal'
            );

            $total = $this->getQuoteTotal($request);


            if ($total >= $freeShippingFrom) {
                $this->logger->info(print_r('Method ' . $this->carrierCode . '/' . $this->methodKey . ' free shipping reason:  config freeshipping ' . $total . ' >= ' . $freeShippingFrom, true));
                return true;
            }
        }

        // cart rules
        if ($allItems = $request->getAllItems()) {
            $hasAllItemsFreeshipping = true;
            foreach ($allItems as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItemId()) {
                    continue;
                }
                if (!$item->getFreeShipping()) {
                    $hasAllItemsFreeshipping = false;
                    break;
                }
            }
            if($hasAllItemsFreeshipping) {
                $this->logger->info(print_r('Method ' . $this->carrierCode . '/' . $this->methodKey . ' free shipping reason: All items set as freeshipping', true));
            }

            return $hasAllItemsFreeshipping;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->methodKey;
    }

    protected function getQuoteTotal(RateRequest $request)
    {
        $total = 0;
        $discountAmount = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
                $discountAmount += $item->getBaseDiscountAmount();
            }
        }

        if ($this->configProvider->getConfigData($this->carrierCode . '/' . $this->methodKey . '/tax_including')) {
            $subTotal = $request->getBaseSubtotalInclTax();
        } else {
            $subTotal = $item->getQuote()->getBaseSubtotal();
        }
        $total = $subTotal - $discountAmount;

        return $total;
    }

    public function setItems($quoteItems)
    {
        $this->quoteItems = $quoteItems;
    }

    public function getName()
    {
        return $this->configProvider->getConfigData(
            $this->carrierCode . '/' . $this->methodKey . '/name'
        );
    }
}

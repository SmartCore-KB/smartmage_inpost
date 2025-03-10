<?php

namespace Smartmage\Inpost\Observer;

use Magento\Framework\DataObject\Copy\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Smartmage\Inpost\Model\ApiShipx\Service\Point\GetPoint;
use Smartmage\Inpost\Model\ConfigProvider;

class SetOrderShippingAddressObserver implements ObserverInterface
{
    protected $fieldsetConfig;

    protected $orderInterface;

    protected GetPoint $pointService;

    protected ConfigProvider $configProvider;

    public function __construct(
        Config $fieldsetConfig,
        OrderInterface $orderInterface,
        GetPoint $pointService,
        ConfigProvider $configProvider
    ) {
        $this->fieldsetConfig = $fieldsetConfig;
        $this->orderInterface = $orderInterface;
        $this->pointService = $pointService;
        $this->configProvider = $configProvider;
    }

    public function execute(Observer $observer)
    {
        if(!$this->configProvider->getChangeShippingAddress()) {
            return $this;
        }
        /** @var Quote $source */
        $source = $observer->getEvent()->getQuote();
        if(
            !$source->getInpostLockerId()
            || strpos($source->getShippingAddress()->getShippingMethod(), 'inpostlocker') === false
        ) {
            return $this;
        }

        /** @var Order $target */
        $target = $observer->getEvent()->getOrder();
        $lockerAddress = $this->pointService->getLockerAddress($source->getInpostLockerId());

        $street = $lockerAddress->street . ' ' . $lockerAddress->building_number
            . ($lockerAddress->flat_number ? '/' . $lockerAddress->flat_number : '');

        $target->getShippingAddress()
            ->setStreet($street)
            ->setCity($lockerAddress->city)
            ->setPostcode($lockerAddress->post_code)
            ->setCountryId('PL')
            ->setRegion($lockerAddress->province);

        return $this;
    }
}

<?php
declare(strict_types=1);
namespace Smartmage\Inpost\Controller\Adminhtml\Shipments;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Ui\Component\MassAction\Filter;
use Smartmage\Inpost\Model\ApiShipx\Service\DispatchOrder\Create as DispatchOrderCreate;
use Smartmage\Inpost\Model\ConfigProvider;
use Smartmage\Inpost\Model\ResourceModel\Shipment\CollectionFactory;

class MassDispatchOrder extends MassActionAbstract
{

    /**
     * @var
     */
    protected $printoutLabels;

    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var \Smartmage\Inpost\Model\ApiShipx\Service\DispatchOrder\Create
     */
    protected $dispatchOrderCreate;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var PsrLoggerInterface
     */
    protected $logger;

    /**
     * MassDispatchOrder constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Smartmage\Inpost\Model\ResourceModel\Shipment\CollectionFactory $collectionFactory
     * @param \Smartmage\Inpost\Model\ConfigProvider $configProvider
     * @param \Smartmage\Inpost\Model\ApiShipx\Service\DispatchOrder\Create $dispatchOrderCreate
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ConfigProvider $configProvider,
        DispatchOrderCreate $dispatchOrderCreate,
        PsrLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->dispatchOrderCreate = $dispatchOrderCreate;
        parent::__construct($context, $filter, $collectionFactory, $configProvider);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Page not found.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $selectedIds = [];
        $omittedIds = [];

        foreach ($collection as $item) {
            if ($item->getSendingMethod() == 'dispatch_order' && !$item->getDispatchOrderId()) {
                $selectedIds[] = $item->getShipmentId();
            } else {
                $omittedIds[] = $item->getShipmentId();
            }
        }

        $dispatchData = [
            'shipments' => $selectedIds,
            'address' => [
                'street' => $this->configProvider->getDefaultPickupStreet(),
                'building_number' => $this->configProvider->getDefaultPickupBuildingNumber(),
                'city' => $this->configProvider->getDefaultPickupCity(),
                'post_code' => $this->configProvider->getDefaultPickupPostCode(),
                'country_code' => $this->configProvider->getDefaultPickupCountryCode(),
            ],
        ];

        try {
            if (!empty($omittedIds)) {
                $this->messageManager->addWarningMessage((count($omittedIds) > 1 ? __('Shipments') : __('Shipment'))
                    . ' ' . implode(', ', $omittedIds)
                    . ' ' . (count($omittedIds) > 1 ? __('have been omitted_m') : __('have been omitted_s')));
            }

            if (!empty($selectedIds)) {
                $this->dispatchOrderCreate->createDispatchOrders($dispatchData);
                $this->messageManager->addSuccessMessage(__('Dispatch order was created'));
            }
        } catch (\Exception $e) {
            $this->logger->info(print_r($e->getMessage(), true));

            $this->messageManager->addExceptionMessage(
                $e
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

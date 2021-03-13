<?php

namespace Smartmage\Inpost\Ui\Component\Form\Element\Address;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class LastName extends \Magento\Ui\Component\Form\Element\Input
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * OrderDetails constructor.
     * @param Http $request
     * @param OrderRepositoryInterface $orderRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param ContextInterface $context
     * @param array $components
     * @param array $data
     */
    public function __construct(
        Http $request,
        OrderRepositoryInterface $orderRepository,
        PriceCurrencyInterface $priceCurrency,
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();

        $config = $this->getData('config');
        $data= $this->request->getParams();
        $order = $this->orderRepository->get($data['order_id']);

        if (isset($config['dataScope']) && $config['dataScope'] == 'last_name') {
            if (isset($data['last_name'])) {
                $config['default'] = $data['last_name'];
            } else {
                $config['default'] = $order->getCustomerLastname();
                $this->setData('config', (array)$config);
            }

        }
    }
}

<?php
declare(strict_types=1);
namespace Smartmage\Inpost\Cron;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Smartmage\Inpost\Model\ApiShipx\Service\Shipment\Search\Multiple as SearchMultiple;

/**
 * Class SyncShipments
 * @package Smartmage\Inpost\Cron
 */
class SyncShipments
{
    /**
     * @var \Smartmage\Inpost\Model\ApiShipx\Service\Shipment\Search\Multiple
     */
    private $searchMultiple;

    /**
     * @var PsrLoggerInterface
     */
    protected $logger;

    /**
     * SyncShipments constructor.
     * @param \Smartmage\Inpost\Model\ApiShipx\Service\Shipment\Search\Multiple $searchMultiple
     */
    public function __construct(
        SearchMultiple $searchMultiple,
        PsrLoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->searchMultiple = $searchMultiple;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info('inpost syn start');
        $this->searchMultiple->getAllShipments();
        $this->logger->info('inpost syn end');

        return $this;
    }
}

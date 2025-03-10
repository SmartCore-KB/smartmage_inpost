<?php

namespace Smartmage\Inpost\Model\ApiShipx\Service\Point;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Smartmage\Inpost\Model\ApiShipx\AbstractService;
use Smartmage\Inpost\Model\ApiShipx\ErrorHandler;
use Smartmage\Inpost\Model\ConfigProvider;
use stdClass;

class GetPoint extends AbstractService
{
    /**
     * GetPoint constructor.
     * @param PsrLoggerInterface $logger
     * @param ConfigProvider $configProvider
     * @param ErrorHandler $errorHandler
     */
    public function __construct(
        PsrLoggerInterface $logger,
        ConfigProvider $configProvider,
        ErrorHandler $errorHandler
    ) {
        //$this->method = CURLOPT_HTTPGET;
        parent::__construct($logger, $configProvider, $errorHandler);
    }

    /**
     * Get locker address
     *
     * @param string $lockerId
     * @return stdClass
     */
    public function getLockerAddress(string $lockerId): stdClass
    {
        $this->callUri = 'v1/points/' . $lockerId;
        $response = $this->getPointService();
        return $response->address_details;
    }

    /**
     * @param $lockerId
     * @return bool
     */
    public function isLockerExist($lockerId): bool
    {
        if (!$lockerId) {
            return false;
        }
        $this->callUri = 'v1/points/' . $lockerId;
        $response = $this->getPointService();

        if (is_object($response) && $response->status != 'Operating') {
            return false;
        }

        return true;
    }

    private function getPointService()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->getBaseUri() . '/' . $this->callUri);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }
}

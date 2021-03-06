<?php

declare(strict_types=1);

namespace Smartmage\Inpost\Model\Carrier\Methods\Courier;

use Smartmage\Inpost\Model\Carrier\AbstractMethod;

class LocalStandard extends AbstractMethod
{
    protected $methodKey = 'localstandard';

    protected $carrierCode = 'inpostcourier';
}

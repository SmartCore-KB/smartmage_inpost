<?php
declare(strict_types=1);

namespace Smartmage\Inpost\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class LabelSize
 */
class LabelSize implements OptionSourceInterface
{
    const A4 = 'a4';
    const A6 = 'a6';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray() : array
    {
        return [
            ['value' => self::A4, 'label' => __('A4')],
            ['value' => self::A6, 'label' => __('A6')],
        ];
    }
}

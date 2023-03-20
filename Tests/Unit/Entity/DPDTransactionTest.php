<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DPDTransactionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(new DPDTransaction(), [
            ['order', new Order()],
            ['labelFile', new File()],
            ['parcelNumbers', []],
        ]);
    }
}

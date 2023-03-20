<?php

namespace Oro\Bundle\DPDBundle\Tests\Units\Method\Identifier;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGenerator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DPDMethodTypeIdentifierGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateIdentifier()
    {
        $channel = $this->createMock(Channel::class);

        $service = $this->createMock(ShippingService::class);
        $service->expects($this->once())
            ->method('getCode')
            ->willReturn('59');

        $generator = new DPDMethodTypeIdentifierGenerator();

        $this->assertEquals('59', $generator->generateIdentifier($channel, $service));
    }
}

<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactory;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DPDShippingMethodTypeFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DPDMethodTypeIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $typeIdentifierGenerator;

    /** @var PackageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $packageProvider;

    /** @var RateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $rateProvider;

    /** @var DPDShippingMethodTypeFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->typeIdentifierGenerator = $this->createMock(DPDMethodTypeIdentifierGeneratorInterface::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->rateProvider = $this->createMock(RateProvider::class);

        $this->factory = new DPDShippingMethodTypeFactory(
            $this->typeIdentifierGenerator,
            $this->packageProvider,
            $this->rateProvider
        );
    }

    public function testCreate()
    {
        $identifier = 'dpd_1_59';
        $label = 'air';

        $settings = $this->createMock(DPDSettings::class);

        $channel = $this->createMock(Channel::class);
        $channel->expects(self::any())
            ->method('getTransport')
            ->willReturn($settings);

        $service = $this->createMock(ShippingService::class);

        $service->expects(self::once())
            ->method('getDescription')
            ->willReturn($label);

        $this->typeIdentifierGenerator->expects(self::once())
            ->method('generateIdentifier')
            ->with($channel, $service)
            ->willReturn($identifier);

        $expected = new DPDShippingMethodType(
            $identifier,
            $label,
            $service,
            $settings,
            $this->packageProvider,
            $this->rateProvider
        );
        self::assertEquals($expected, $this->factory->create($channel, $service));
    }
}

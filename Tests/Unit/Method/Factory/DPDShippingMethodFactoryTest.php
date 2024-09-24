<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\Factory\DPDHandlerFactoryInterface;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodFactory;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactoryInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class DPDShippingMethodFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodIdentifierGenerator;

    /** @var DPDShippingMethodTypeFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodTypeFactory;

    /** @var DPDHandlerFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $handlerFactory;

    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIconProvider;

    /** @var DPDShippingMethodFactory */
    private $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->methodIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->methodTypeFactory = $this->createMock(DPDShippingMethodTypeFactoryInterface::class);
        $this->handlerFactory = $this->createMock(DPDHandlerFactoryInterface::class);
        $this->integrationIconProvider = $this->createMock(IntegrationIconProviderInterface::class);

        $this->factory = new DPDShippingMethodFactory(
            $this->localizationHelper,
            $this->methodIdentifierGenerator,
            $this->methodTypeFactory,
            $this->handlerFactory,
            $this->integrationIconProvider
        );
    }

    public function testCreate(): void
    {
        $identifier = 'dpd_1';
        $enabled = true;
        $name = 'DPD';
        $label = 'label';
        $iconUri = 'bundles/icon-uri.png';

        $transport = $this->createMock(DPDSettings::class);

        $channel = new Channel();
        $channel->setName($name);
        $channel->setTransport($transport);
        $channel->setEnabled($enabled);

        $type1 = $this->createMock(ShippingMethodTypeInterface::class);
        $type2 = $this->createMock(ShippingMethodTypeInterface::class);

        $service1 = $this->createMock(ShippingService::class);
        $service2 = $this->createMock(ShippingService::class);
        $this->methodTypeFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$channel, $service1], [$channel, $service2])
            ->willReturnOnConsecutiveCalls($type1, $type2);

        $handler1 = $this->createMock(DPDHandlerInterface::class);
        $handler2 = $this->createMock(DPDHandlerInterface::class);
        $this->handlerFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$channel, $service1], [$channel, $service2])
            ->willReturnOnConsecutiveCalls($handler1, $handler2);

        $serviceCollection = $this->createMock(Collection::class);
        $serviceCollection->expects(self::once())
            ->method('toArray')
            ->willReturn([$service1, $service2]);

        $transport->expects(self::once())
            ->method('getApplicableShippingServices')
            ->willReturn($serviceCollection);

        $this->methodIdentifierGenerator->expects(self::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $labelsCollection = $this->createMock(Collection::class);
        $transport->expects(self::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn($label);

        $this->integrationIconProvider->expects(self::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $expected = new DPDShippingMethod(
            $identifier,
            $name,
            $label,
            $enabled,
            $iconUri,
            [$type1, $type2],
            [$handler1, $handler2]
        );
        self::assertEquals($expected, $this->factory->create($channel));
    }
}

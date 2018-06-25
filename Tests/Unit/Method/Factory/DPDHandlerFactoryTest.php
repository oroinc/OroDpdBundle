<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\DPDBundle\Method\Factory\DPDHandlerFactory;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactory;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;

class DPDHandlerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $typeIdentifierGenerator;

    /**
     * @var DPDTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transport;

    /**
     * @var PackageProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $packageProvider;

    /**
     * @var DPDRequestFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dpdRequestFactory;

    /**
     * @var ZipCodeRulesCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $zipCodeRulesCache;

    /**
     * @var OrderShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingLineItemConverter;

    /**
     * @var DPDShippingMethodTypeFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->typeIdentifierGenerator = $this->createMock(DPDMethodTypeIdentifierGeneratorInterface::class);
        $this->transport = $this->createMock(DPDTransport::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->dpdRequestFactory = $this->createMock(DPDRequestFactory::class);
        $this->zipCodeRulesCache = $this->createMock(ZipCodeRulesCache::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);

        $this->factory = new DPDHandlerFactory(
            $this->typeIdentifierGenerator,
            $this->transport,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
        );
    }

    public function testCreate()
    {
        $identifier = 'dpd_1_59';
        $methodId = 'dpd_1';

        /** @var DPDSettings|\PHPUnit\Framework\MockObject\MockObject $settings */
        $settings = $this->createMock(DPDSettings::class);

        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($settings);

        /** @var ShippingService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->createMock(ShippingService::class);

        $this->typeIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel, $service)
            ->willReturn($identifier);

        $this->assertEquals(new DPDHandler(
            $identifier,
            $service,
            $settings,
            $this->transport,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
        ), $this->factory->create($channel, $service));
    }
}

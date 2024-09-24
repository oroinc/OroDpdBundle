<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class RateProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DPDTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var RateProvider */
    private $rateProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->transport = $this->createMock(DPDTransport::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $measureUnitConversion = $this->createMock(MeasureUnitConversion::class);
        $measureUnitConversion->expects(self::any())
            ->method('convert')
            ->willReturnArgument(0);

        $this->rateProvider = new RateProvider(
            $this->doctrine,
            $measureUnitConversion
        );
    }

    public function testGetRateValueFlatPolicy()
    {
        $this->transport->expects(self::any())
            ->method('getRatePolicy')
            ->willReturn(DPDTransport::FLAT_RATE_POLICY);
        $this->transport->expects(self::once())
            ->method('getFlatRatePriceValue')
            ->willReturn('1.0');

        self::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress())
        );
    }

    public function testGetRateValueTablePolicy()
    {
        $this->transport->expects(self::any())
            ->method('getRatePolicy')
            ->willReturn(DPDTransport::TABLE_RATE_POLICY);

        $repository = $this->createMock(RateRepository::class);
        $repository->expects(self::once())
            ->method('findFirstRateByServiceAndDestination')
            ->willReturn((new Rate())->setPriceValue('1.0'));

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        self::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress())
        );
    }
}

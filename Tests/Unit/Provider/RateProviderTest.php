<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RateProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DPDTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var RateProvider
     */
    protected $rateProvider;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $measureUnitConversion;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(DPDTransport::class);

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $this->measureUnitConversion = $this->getMockBuilder(MeasureUnitConversion::class)
            ->disableOriginalConstructor()->getMock();
        $this->measureUnitConversion->expects(static::any())->method('convert')->willReturnCallback(
            function () {
                $args = func_get_args();

                return $args[0];
            }
        );

        $this->rateProvider = new RateProvider(
            $this->registry,
            $this->measureUnitConversion
        );
    }

    public function testGetRateValueFlatPolicy()
    {
        $this->transport->expects(self::any())->method('getRatePolicy')->willReturn(DPDTransport::FLAT_RATE_POLICY);
        $this->transport->expects(self::once())->method('getFlatRatePriceValue')->willReturn('1.0');

        static::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress())
        );
    }

    public function testGetRateValueTablePolicy()
    {
        $this->transport->expects(self::any())->method('getRatePolicy')->willReturn(DPDTransport::TABLE_RATE_POLICY);

        $repository = $this->createMock(RateRepository::class);
        $repository
            ->expects(self::once())
            ->method('findFirstRateByServiceAndDestination')
            ->willReturn((new Rate())->setPriceValue('1.0'));

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::once())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::once())->method('getManagerForClass')->willReturn($manager);

        static::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress())
        );
    }
}

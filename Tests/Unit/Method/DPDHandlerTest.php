<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCacheKey;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DPDHandlerTest extends TestCase
{
    use EntityTrait;

    private const IDENTIFIER = '02';
    private const LABEL = 'service_code_label';

    private DPDTransport|MockObject $transport;

    private DPDTransportProvider|MockObject $transportProvider;

    private PackageProvider|MockObject $packageProvider;

    private ShippingService|MockObject $shippingService;

    private DPDRequestFactory|MockObject $dpdRequestFactory;

    private DPDHandlerInterface $dpdHandler;

    private CacheInterface|MockObject $cache;

    private OrderShippingLineItemConverterInterface|MockObject $shippingLineItemConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->transport = $this->getEntity(
            DPDTransport::class,
            [
                'dpdTestMode' => false,
                'cloudUserId' => 'some cloud user id',
                'cloudUserToken' => 'some cloud user token',
                'unitOfWeight' => ((new WeightUnit())->setCode('kg')),
                'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
                'flatRatePriceValue' => '50.000',
                'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
                'invalidate_cache_at' => new \DateTime('2020-01-01'),
                'applicableShippingServices' => [new ShippingService()],
            ]
        );

        $this->transportProvider = $this->createMock(DPDTransportProvider::class);
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->dpdRequestFactory = $this->createMock(DPDRequestFactory::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);

        $this->dpdHandler = new DPDHandler(
            self::IDENTIFIER,
            $this->shippingService,
            $this->transport,
            $this->transportProvider,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->cache,
            $this->shippingLineItemConverter,
            new \DateTime('2017-01-30 00:00')
        );
    }

    /**
     * @dataProvider testShipOrderProvider
     */
    public function testShipOrder(array $packageList, ?SetOrderResponse $expectedResponse): void
    {
        /** @var Order $order */
        $order = $this->getEntity(
            Order::class,
            [
                'id' => 1,
                'shippingAddress' => new OrderAddress(),
                'customerUser' => (new CustomerUser())->setEmail('an@email.com'),
            ]
        );

        $lineItems = new ArrayCollection([]);
        $this->shippingLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->willReturn($lineItems);

        $this->packageProvider->expects(self::once())
            ->method('createPackages')
            ->willReturn($packageList);

        $request = $this->createMock(SetOrderRequest::class);
        $this->dpdRequestFactory->expects(self::atMost(1))
            ->method('createSetOrderRequest')
            ->willReturn($request);

        $response = $this->createMock(SetOrderResponse::class);
        $this->transportProvider->expects(self::atMost(1))
            ->method('getSetOrderResponse')
            ->willReturn($response);

        $response = $this->dpdHandler->shipOrder($order, new \DateTime());

        self::assertEquals($expectedResponse, $response);
    }

    public function testShipOrderProvider(): array
    {
        return [
            'OnePackage' => [
                'packageList' => [new Package()],
                'expectedResponse' => $this->createMock(SetOrderResponse::class),
            ],
            'NoPackage' => [
                'packageList' => [],
                'expectedResponse' => null,
            ],
            'TwoPackages' => [
                'packageList' => [new Package(), new Package()],
                'expectedResponse' => null,
            ],
        ];
    }

    public function testFetchZipCodeRulesCache(): void
    {
        $request = $this->createMock(ZipCodeRulesRequest::class);
        $this->dpdRequestFactory->expects(self::any())
            ->method('createZipCodeRulesRequest')
            ->willReturn($request);

        $response = new ZipCodeRulesResponse();

        $this->transportProvider->expects(self::any())
            ->method('getZipCodeRulesResponse')
            ->willReturn($response);

        $cacheKey = (new ZipCodeRulesCacheKey())
            ->setTransport($this->transport)
            ->setZipCodeRulesRequest($request);
        $invalidateAt = '';
        if ($cacheKey->getTransport() && $cacheKey->getTransport()->getInvalidateCacheAt()) {
            $invalidateAt = $cacheKey->getTransport()->getInvalidateCacheAt()->getTimestamp();
        }
        $key = implode('_', [
            $cacheKey->generateKey(),
            $invalidateAt,
        ]);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($key)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
        self::assertEquals($response, $this->dpdHandler->fetchZipCodeRules());
    }

    /**
     * @dataProvider testGetNextPickupDayProvider
     */
    public function testGetNextPickupDay(
        \DateTime $shipDate,
        bool $isExpressService,
        ?string $classicCutOff,
        ?string $expressCutOff,
        array $noPickupDays,
        \DateTime $expectedResult
    ): void {
        $this->shippingService->expects(self::any())
            ->method('isExpressService')
            ->willReturn($isExpressService);

        $request = $this->createMock(ZipCodeRulesRequest::class);
        $this->dpdRequestFactory->expects(self::any())
            ->method('createZipCodeRulesRequest')
            ->willReturn($request);

        $response = $this->createMock(ZipCodeRulesResponse::class);
        $response->expects(self::any())
            ->method('getClassicCutOff')
            ->willReturn($classicCutOff);
        $response->expects(self::any())
            ->method('getExpressCutOff')
            ->willReturn($expressCutOff);
        $response->expects(self::any())
            ->method('isNoPickupDay')
            ->willReturnCallback(function (\DateTime $date) use ($noPickupDays) {
                return array_key_exists($date->format('Y-m-d'), array_flip($noPickupDays));
            });

        $cacheKey = (new ZipCodeRulesCacheKey())
            ->setTransport($this->transport)
            ->setZipCodeRulesRequest($request);
        $invalidateAt = '';
        if ($cacheKey->getTransport() && $cacheKey->getTransport()->getInvalidateCacheAt()) {
            $invalidateAt = $cacheKey->getTransport()->getInvalidateCacheAt()->getTimestamp();
        }
        $key = implode('_', [
            $cacheKey->generateKey(),
            $invalidateAt,
        ]);

        $this->cache->expects(self::any())
            ->method('get')
            ->with($key)
            ->willReturn($response);

        self::assertEquals($expectedResult, $this->dpdHandler->getNextPickupDay($shipDate));
    }

    public function testGetNextPickupDayProvider(): array
    {
        return [
            'classic_today_before_cutoff' => [
                'shipDate' => new \DateTime('2017-01-30 00:00'),
                'isExpressService' => false,
                'classicCutOff' => '18:00',
                'expressCutOff' => null,
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-01-30 00:00'),
            ],
            'classic_today_after_cutoff' => [
                'shipDate' => new \DateTime('2017-01-30 19:00'),
                'isExpressService' => false,
                'classicCutOff' => '18:00',
                'expressCutOff' => null,
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-01-31 19:00'),
            ],
            'classic_today_after_cutoff_next_day_no_pickup' => [
                'shipDate' => new \DateTime('2017-01-30 19:00'),
                'isExpressService' => false,
                'classicCutOff' => '18:00',
                'expressCutOff' => null,
                'noPickupDays' => ['2017-01-31'],
                'expectedResult' => new \DateTime('2017-02-01 19:00'),
            ],
            'express_today_before_cutoff' => [
                'shipDate' => new \DateTime('2017-01-30 00:00'),
                'isExpressService' => true,
                'classicCutOff' => '18:00',
                'expressCutOff' => '18:00',
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-01-30 00:00'),
            ],
            'express_today_after_cutoff' => [
                'shipDate' => new \DateTime('2017-01-30 19:00'),
                'isExpressService' => true,
                'classicCutOff' => '18:00',
                'expressCutOff' => '18:00',
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-01-31 19:00'),
            ],
            'pickup_day' => [
                'shipDate' => new \DateTime('2017-01-31 00:00'),
                'isExpressService' => false,
                'classicCutOff' => null,
                'expressCutOff' => null,
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-01-31 00:00'),
            ],
            'no_pickup_day' => [
                'shipDate' => new \DateTime('2017-01-31 00:00'),
                'isExpressService' => false,
                'classicCutOff' => null,
                'expressCutOff' => null,
                'noPickupDays' => ['2017-01-31'],
                'expectedResult' => new \DateTime('2017-02-01 00:00'),
            ],
            'saturday' => [
                'shipDate' => new \DateTime('2017-02-04 00:00'),
                'isExpressService' => false,
                'classicCutOff' => null,
                'expressCutOff' => null,
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-02-06 00:00'),
            ],
            'saturday_then_monday_no_pickup' => [
                'shipDate' => new \DateTime('2017-02-04 00:00'),
                'isExpressService' => false,
                'classicCutOff' => null,
                'expressCutOff' => null,
                'noPickupDays' => ['2017-02-06'],
                'expectedResult' => new \DateTime('2017-02-07 00:00'),
            ],
            'sunday' => [
                'shipDate' => new \DateTime('2017-02-05 00:00'),
                'isExpressService' => false,
                'classicCutOff' => null,
                'expressCutOff' => null,
                'noPickupDays' => [],
                'expectedResult' => new \DateTime('2017-02-06 00:00'),
            ],
        ];
    }
}

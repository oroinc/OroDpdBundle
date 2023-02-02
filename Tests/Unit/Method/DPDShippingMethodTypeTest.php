<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;

class DPDShippingMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER = '02';
    private const LABEL = 'service_code_label';

    /** @var PackageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $packageProvider;

    /** @var RateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $rateProvider;

    /** @var ShippingService|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingService;

    /** @var DPDShippingMethodType */
    private $dpdShippingMethodType;

    protected function setUp(): void
    {
        $transport = new DPDTransport();
        $transport->setDPDTestMode(false);
        $transport->setCloudUserId('some cloud user id');
        $transport->setCloudUserToken('some cloud user token');
        $transport->setUnitOfWeight((new WeightUnit())->setCode('kg'));
        $transport->setRatePolicy(DPDTransport::FLAT_RATE_POLICY);
        $transport->setFlatRatePriceValue('50.000');
        $transport->setLabelSize(DPDTransport::PDF_A4_LABEL_SIZE);
        $transport->setLabelStartPosition(DPDTransport::UPPERLEFT_LABEL_START_POSITION);
        $transport->setInvalidateCacheAt(new \DateTime('2020-01-01'));
        $transport->addApplicableShippingService(new ShippingService());

        $this->shippingService = $this->createMock(ShippingService::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->rateProvider = $this->createMock(RateProvider::class);

        $this->dpdShippingMethodType = new DPDShippingMethodType(
            self::IDENTIFIER,
            self::LABEL,
            $this->shippingService,
            $transport,
            $this->packageProvider,
            $this->rateProvider
        );
    }

    public function testGetOptionsConfigurationFormType()
    {
        self::assertEquals(
            DPDShippingMethodOptionsType::class,
            $this->dpdShippingMethodType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        self::assertEquals(0, $this->dpdShippingMethodType->getSortOrder());
    }

    /**
     * @dataProvider calculatePriceDataProvider
     */
    public function testCalculatePrice(
        int $ratePrice,
        int $methodHandlingFee,
        int $typeHandlingFee,
        int $expectedPrice
    ) {
        $context = $this->createMock(ShippingContextInterface::class);
        $lineItems = $this->createMock(ShippingLineItemCollectionInterface::class);
        $context->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $context->expects(self::any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $context->expects(self::once())
            ->method('getCurrency')
            ->willReturn('USD');

        $methodOptions = ['handling_fee' => $methodHandlingFee];
        $this->shippingService->expects(self::any())
            ->method('getCode')
            ->willReturn(self::IDENTIFIER);
        $typeOptions = ['handling_fee' => $typeHandlingFee];

        $this->packageProvider->expects(self::once())
            ->method('createPackages')
            ->willReturn([new Package()]);
        $this->rateProvider->expects(self::once())
            ->method('getRateValue')
            ->willReturn($ratePrice);

        $price = $this->dpdShippingMethodType->calculatePrice($context, $methodOptions, $typeOptions);

        self::assertEquals(Price::create($expectedPrice, 'USD'), $price);
    }

    public function calculatePriceDataProvider(): array
    {
        return [
            'TypeSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 0,
                'typeHandlingFee' => 5,
                'expectedPrice' => 55,
            ],
            'MethodSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 3,
                'typeHandlingFee' => 0,
                'expectedPrice' => 53,
            ],
            'Method&TypeSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 3,
                'typeHandlingFee' => 5,
                'expectedPrice' => 58,
            ],
            'NoSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 0,
                'typeHandlingFee' => 0,
                'expectedPrice' => 50,
            ],
        ];
    }
}

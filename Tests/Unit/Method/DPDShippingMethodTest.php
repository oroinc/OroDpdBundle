<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DPDShippingMethodTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER = 'dpd_1';
    private const NAME = 'DPD';
    private const LABEL = 'dpd_label';
    private const TYPE_IDENTIFIER = '59';
    private const ICON = 'dpd.png';

    private DPDShippingMethod $dpdShippingMethod;

    protected function setUp(): void
    {
        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);
        $type->expects(self::any())
            ->method('calculatePrice')
            ->willReturn(Price::create('1.0', 'USD'));

        $handler = $this->createMock(DPDHandlerInterface::class);
        $handler->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);

        $this->dpdShippingMethod = new DPDShippingMethod(
            self::IDENTIFIER,
            self::NAME,
            self::LABEL,
            true,
            self::ICON,
            [$type],
            [$handler]
        );
    }

    public function testIsGrouped()
    {
        self::assertTrue($this->dpdShippingMethod->isGrouped());
    }

    public function testGetIdentifier()
    {
        self::assertEquals(self::IDENTIFIER, $this->dpdShippingMethod->getIdentifier());
    }

    public function testGetName()
    {
        self::assertEquals(self::NAME, $this->dpdShippingMethod->getName());
    }

    public function testGetLabel()
    {
        self::assertEquals(self::LABEL, $this->dpdShippingMethod->getLabel());
    }

    public function testGetIcon()
    {
        self::assertEquals(self::ICON, $this->dpdShippingMethod->getIcon());
    }

    public function testGetTypes()
    {
        $types = $this->dpdShippingMethod->getTypes();

        self::assertCount(1, $types);
        self::assertEquals(self::TYPE_IDENTIFIER, $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = self::TYPE_IDENTIFIER;
        $type = $this->dpdShippingMethod->getType($identifier);

        self::assertInstanceOf(ShippingMethodTypeInterface::class, $type);
        self::assertEquals(self::TYPE_IDENTIFIER, $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->dpdShippingMethod->getOptionsConfigurationFormType();

        self::assertEquals(HiddenType::class, $type);
    }

    public function testGetSortOrder()
    {
        self::assertEquals('20', $this->dpdShippingMethod->getSortOrder());
    }

    public function testCalculatePrices()
    {
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['handling_fee' => null];
        $optionsByTypes = [self::TYPE_IDENTIFIER => ['handling_fee' => null]];

        $prices = $this->dpdShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        self::assertCount(1, $prices);
        self::assertArrayHasKey(self::TYPE_IDENTIFIER, $prices);
        self::assertEquals(Price::create('1.0', 'USD'), $prices[self::TYPE_IDENTIFIER]);
    }

    /**
     * @dataProvider trackingDataProvider
     */
    public function testGetTrackingLink(string $number, ?string $resultURL)
    {
        self::assertEquals($resultURL, $this->dpdShippingMethod->getTrackingLink($number));
    }

    public function trackingDataProvider(): array
    {
        return [
            'emptyTrackingNumber' => [
                'number' => '',
                'resultURL' => null,
            ],
            'wrongTrackingNumber2' => [
                'number' => '123123123123',
                'resultURL' => null,
            ],
            'rightTrackingNumber' => [
                'number' => '09980525414724',
                'resultURL' => 'https://tracking.dpd.de/parcelstatus?query=09980525414724',
            ],
        ];
    }
}

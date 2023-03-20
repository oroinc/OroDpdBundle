<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Component\Testing\Unit\EntityTrait;

class PackageProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var PackageProvider */
    private $packageProvider;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $measureUnitConversion = $this->createMock(MeasureUnitConversion::class);
        $measureUnitConversion->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function () {
                $args = func_get_args();

                return $args[0];
            });

        $this->packageProvider = new PackageProvider($measureUnitConversion, $this->localizationHelper);
    }

    /**
     * @dataProvider packagesDataProvider
     */
    public function testCreatePackages(int $lineItemCnt, int|float $productWeight, ?array $expectedPackages)
    {
        $this->localizationHelper->expects(self::any())
            ->method('getLocalizedValue')->willReturn('product name');

        $lineItems = [];
        $allProductsShippingOptions = [];
        for ($i = 1; $i <= $lineItemCnt; ++$i) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $i]);

            $lineItems[] = $this->createShippingLineItem($product, $productWeight);

            /* @var ProductShippingOptions $productShippingOptions */
            $allProductsShippingOptions[] = $this->createProductShippingOptions($product, $productWeight);
        }

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($lineItems),
        ]);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::any())
            ->method('findBy')
            ->willReturn($allProductsShippingOptions);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $packages = $this->packageProvider->createPackages($context->getLineItems());

        self::assertEquals($expectedPackages, $packages);
    }

    public function packagesDataProvider(): array
    {
        return [
            'OnePackage' => [
                'lineItemCnt' => 2,
                'productWeight' => 15,
                'expectedPackages' => [
                    (new Package())->setWeight(30)->setContents('product name,product name'),
                ],
            ],
            'TwoPackages' => [
                'lineItemCnt' => 2,
                'productWeight' => 30,
                'expectedPackages' => [
                    (new Package())->setWeight(30)->setContents('product name'),
                    (new Package())->setWeight(30)->setContents('product name'),
                ],
            ],
            'TooBigToFit' => [
                'lineItemCnt' => 2,
                'productWeight' => PackageProvider::MAX_PACKAGE_WEIGHT_KGS + 1,
                'expectedPackages' => null,
            ],
            'NoPackages' => [
                'lineItemCnt' => 0,
                'productWeight' => 30,
                'expectedPackages' => null,
            ],
        ];
    }

    private function createShippingLineItem(Product $product, float $productWeight): ShippingLineItem
    {
        return new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT => $product,
            ShippingLineItem::FIELD_QUANTITY => 1,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->getEntity(
                ProductUnit::class,
                ['code' => 'test1']
            ),
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'test1',
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 1,
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(7, 7, 7, (new LengthUnit())->setCode('inch')),
            ShippingLineItem::FIELD_WEIGHT => Weight::create($productWeight, $this->getEntity(
                WeightUnit::class,
                ['code' => 'lbs']
            )),
        ]);
    }

    private function createProductShippingOptions(Product $product, float $productWeight): ProductShippingOptions
    {
        return $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
                'product' => $product,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 7, 7, (new LengthUnit())->setCode('inch')),
                'weight' => Weight::create($productWeight, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'kg']
                )),
            ]
        );
    }
}

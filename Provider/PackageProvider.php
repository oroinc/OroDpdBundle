<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class PackageProvider
{
    const MAX_PACKAGE_WEIGHT_KGS = 31.5; //as defined on dpd api documentation
    const UNIT_OF_WEIGHT = 'kg'; //dpd only supports kg

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /**
     * @param MeasureUnitConversion $measureUnitConversion
     * @param LocalizationHelper    $localizationHelper
     */
    public function __construct(MeasureUnitConversion $measureUnitConversion, LocalizationHelper $localizationHelper)
    {
        $this->measureUnitConversion = $measureUnitConversion;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return null|\Oro\Bundle\DPDBundle\Model\Package[]
     */
    public function createPackages(ShippingLineItemCollectionInterface $lineItems)
    {
        if ($lineItems->isEmpty()) {
            return null;
        }

        $packages = [];
        $productsInfoByUnit = $this->getProductInfoByUnit($lineItems);
        if (count($productsInfoByUnit) > 0) {
            $weight = 0;
            $contents = [];
            /** @var array $unit */
            foreach ($productsInfoByUnit as $unit) {
                if ($unit['weight'] > static::MAX_PACKAGE_WEIGHT_KGS) {
                    return null;
                }
                if (($weight + $unit['weight']) > static::MAX_PACKAGE_WEIGHT_KGS) {
                    $packages[] = (new Package())
                        ->setWeight($weight)
                        ->setContents(implode(',', $contents));

                    $weight = 0;
                    $contents = [];
                }

                $weight += $unit['weight'];
                $contents[$unit['productId']] = $unit['productName'];
            }

            if ($weight > 0) {
                $packages[] = (new Package())
                    ->setWeight($weight)
                    ->setContents(implode(',', $contents));
            }
        }

        return $packages;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    protected function getProductInfoByUnit(ShippingLineItemCollectionInterface $lineItems)
    {
        $productsInfoByUnit = [];

        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();

            if ($product === null) {
                return [];
            }

            //$productDefaultName = $product->getDefaultName()->getString();
            $productName = (string)$this->localizationHelper->getLocalizedValue($product->getNames());

            $dpdWeight = null;
            $lineItemWeight = $lineItem->getWeight();

            if ($lineItemWeight !== null && $lineItemWeight->getValue()) {
                $dpdWeight = $this->measureUnitConversion->convert($lineItemWeight, static::UNIT_OF_WEIGHT);

                $dpdWeight = $dpdWeight !== null ? $dpdWeight->getValue() : null;
            }
            if (!$dpdWeight) {
                return [];
            }

            for ($i = 0; $i < $lineItem->getQuantity(); ++$i) {
                $productsInfoByUnit[] = [
                    'productId' => $product->getId(),
                    'productName' => $productName,
                    'weightUnit' => static::UNIT_OF_WEIGHT,
                    'weight' => $dpdWeight,
                ];
            }
        }

        return $productsInfoByUnit;
    }
}

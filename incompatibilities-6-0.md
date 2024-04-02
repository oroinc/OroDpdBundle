DPDBundle
---------
* The following methods in class `PackageProvider`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/6.0.0/Provider/PackageProvider.php#L36 "Oro\Bundle\DPDBundle\Provider\PackageProvider")</sup> were changed:
  > - `createPackages(ShippingLineItemCollectionInterface $lineItems)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Provider/PackageProvider.php#L32 "Oro\Bundle\DPDBundle\Provider\PackageProvider")</sup>
  > - `createPackages(Collection $lineItems)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/6.0.0/Provider/PackageProvider.php#L36 "Oro\Bundle\DPDBundle\Provider\PackageProvider")</sup>

  > - `getProductInfoByUnit(ShippingLineItemCollectionInterface $lineItems)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Provider/PackageProvider.php#L78 "Oro\Bundle\DPDBundle\Provider\PackageProvider")</sup>
  > - `getProductInfoByUnit(Collection $lineItems)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/6.0.0/Provider/PackageProvider.php#L82 "Oro\Bundle\DPDBundle\Provider\PackageProvider")</sup>

* The `DPDShippingMethod::__construct($identifier, $label, $isEnabled, $icon, $types, $handlers)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Method/DPDShippingMethod.php#L36 "Oro\Bundle\DPDBundle\Method\DPDShippingMethod")</sup> method was changed to `DPDShippingMethod::__construct($identifier, $name, $label, $isEnabled, $icon, $types, $handlers)`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/6.0.0/Method/DPDShippingMethod.php#L37 "Oro\Bundle\DPDBundle\Method\DPDShippingMethod")</sup>
* The `OroDPDBundleInstaller::setContainer`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Schema/OroDPDBundleInstaller.php#L24 "Oro\Bundle\DPDBundle\Migrations\Schema\OroDPDBundleInstaller::setContainer")</sup> method was removed.
* The following methods in class `LoadShippingServicesData`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L25 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData")</sup> were removed:
   - `setContainer`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L25 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData::setContainer")</sup>
   - `getShippingServicesData`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L44 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData::getShippingServicesData")</sup>
* The following properties in class `LoadShippingServicesData`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L15 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData")</sup> were removed:
   - `$loadedCountries`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L15 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData::$loadedCountries")</sup>
   - `$container`<sup>[[?]](https://github.com/oroinc/OroDpdBundle/tree/5.1.0/Migrations/Data/ORM/LoadShippingServicesData.php#L20 "Oro\Bundle\DPDBundle\Migrations\Data\ORM\LoadShippingServicesData::$container")</sup>


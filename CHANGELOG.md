The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## Changes in the DPD package versions

- [6.0.0-RC](#600-rc-2024-02-29)
- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.0](#420-2020-01-29)
- [4.0.0](#400-2019-07-31)
- [1.5.0](#150-2017-11-30)

## 6.0.0-RC (2024-02-29)
[Show detailed list of changes](incompatibilities-6-0-rc.md)

### Changed
* Changed `\Oro\Bundle\DPDBundle\Provider\PackageProvider::createPackages` so it expects `\Oro\Bundle\ShippingBundle\Context\ShippingLineItem` argument instead of the removed `\Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface`.

## 5.1.0 (2023-03-31)

[Show detailed list of changes](incompatibilities-5-1.md)

## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

## 1.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-1-5.md)

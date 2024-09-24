<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Represents DPD shipping method type.
 */
class DPDShippingMethodType implements ShippingMethodTypeInterface
{
    private string $identifier;
    private string $label;
    private DPDSettings $transport;
    private ShippingService $shippingService;
    private PackageProvider $packageProvider;
    private RateProvider $rateProvider;

    public function __construct(
        string $identifier,
        string $label,
        ShippingService $shippingService,
        DPDSettings $transport,
        PackageProvider $packageProvider,
        RateProvider $rateProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->shippingService = $shippingService;
        $this->transport = $transport;
        $this->packageProvider = $packageProvider;
        $this->rateProvider = $rateProvider;
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return 0;
    }

    #[\Override]
    public function getOptionsConfigurationFormType(): ?string
    {
        return DPDShippingMethodOptionsType::class;
    }

    #[\Override]
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        if (!$context->getShippingAddress()) {
            return null;
        }

        $packageList = $this->packageProvider->createPackages($context->getLineItems());
        if (!$packageList || \count($packageList) !== 1) {
            return null;
        }

        $rateValue = $this->rateProvider->getRateValue(
            $this->transport,
            $this->shippingService,
            $context->getShippingAddress()
        );

        if ($rateValue === null) {
            return null;
        }

        $optionsDefaults = [
            DPDShippingMethod::HANDLING_FEE_OPTION => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        $handlingFee =
            $methodOptions[DPDShippingMethod::HANDLING_FEE_OPTION] +
            $typeOptions[DPDShippingMethod::HANDLING_FEE_OPTION];

        return Price::create((float) $rateValue + (float) $handlingFee, $context->getCurrency());
    }
}

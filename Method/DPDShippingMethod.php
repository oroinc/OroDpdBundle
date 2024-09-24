<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Represents DPD shipping method.
 */
class DPDShippingMethod implements
    ShippingMethodInterface,
    ShippingTrackingAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingMethodIconAwareInterface
{
    public const HANDLING_FEE_OPTION = 'handling_fee';

    private const TRACKING_URL = 'https://tracking.dpd.de/parcelstatus?query=';
    private const TRACKING_REGEX = '/\b 0 [0-9]{13}\b/x';

    private string $identifier;
    private string $name;
    private string $label;
    private bool $isEnabled;
    private ?string $icon;
    /** @var ShippingMethodTypeInterface[] */
    private array $types;
    /** @var DPDHandlerInterface[] */
    private array $handlers;

    public function __construct(
        string $identifier,
        string $name,
        string $label,
        bool $isEnabled,
        ?string $icon,
        array $types,
        array $handlers
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->label = $label;
        $this->isEnabled = $isEnabled;
        $this->icon = $icon;
        $this->types = $types;
        $this->handlers = $handlers;
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    #[\Override]
    public function isGrouped(): bool
    {
        return true;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    #[\Override]
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    #[\Override]
    public function getTypes(): array
    {
        return $this->types;
    }

    #[\Override]
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        $methodTypes = $this->getTypes();
        if (null !== $methodTypes) {
            foreach ($methodTypes as $methodType) {
                if ($methodType->getIdentifier() === $identifier) {
                    return $methodType;
                }
            }
        }

        return null;
    }

    #[\Override]
    public function getOptionsConfigurationFormType(): ?string
    {
        return HiddenType::class;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return 20;
    }

    #[\Override]
    public function getTrackingLink(string $number): ?string
    {
        if (!preg_match(self::TRACKING_REGEX, $number, $match)) {
            return null;
        }

        return self::TRACKING_URL . $match[0];
    }

    #[\Override]
    public function calculatePrices(
        ShippingContextInterface $context,
        array $methodOptions,
        array $optionsByTypes
    ): array {
        if (\count($this->getTypes()) === 0) {
            return [];
        }

        $prices = [];
        foreach ($optionsByTypes as $typeId => $typeOptions) {
            $prices[$typeId] = $this->getType($typeId)->calculatePrice($context, $methodOptions, $typeOptions);
        }

        return $prices;
    }

    /**
     * @return DPDHandlerInterface[]
     */
    public function getDPDHandlers(): array
    {
        return $this->handlers;
    }

    public function getDPDHandler(string $identifier): ?DPDHandlerInterface
    {
        $handlers = $this->getDPDHandlers();
        if ($handlers !== null) {
            foreach ($handlers as $handler) {
                if ($handler->getIdentifier() === $identifier) {
                    return $handler;
                }
            }
        }

        return null;
    }
}

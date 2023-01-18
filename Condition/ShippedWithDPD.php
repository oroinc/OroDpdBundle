<?php

namespace Oro\Bundle\DPDBundle\Condition;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

/**
 * Check if an order shipping method matches any DPD method
 * Usage:.
 *
 *   Check order shipping method is DPD, $.data is an order entity:
 *
 *      @shipped_with_dpd: $.data.shippingMethod
 */
class ShippedWithDPD extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ShippingMethodProviderInterface $shippingProvider;
    private mixed $value = null;

    public function __construct(ShippingMethodProviderInterface $shippingProvider)
    {
        $this->shippingProvider = $shippingProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $shippingMethod = $this->resolveValue($context, $this->value);

        return $shippingMethod && $this->shippingProvider->hasShippingMethod($shippingMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'shipped_with_dpd';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->value, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ value }}' => $this->resolveValue($context, $this->value),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->value = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }
}

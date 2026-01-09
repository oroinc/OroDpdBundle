<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validates that DPD shipping service is not used in any shipping method.
 */
class RemoveUsedShippingServiceValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_dpd_remove_used_shipping_service_validator';

    /**
     * @internal
     */
    public const VIOLATION_PATH = 'applicableShippingServices';

    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $integrationShippingMethodFactory;

    /**
     * @var ShippingMethodValidatorInterface
     */
    private $shippingMethodValidator;

    public function __construct(
        IntegrationShippingMethodFactoryInterface $integrationShippingMethodFactory,
        ShippingMethodValidatorInterface $shippingMethodValidator
    ) {
        $this->integrationShippingMethodFactory = $integrationShippingMethodFactory;
        $this->shippingMethodValidator = $shippingMethodValidator;
    }

    /**
     * @param DPDTransport                                   $value
     * @param Constraint|RemoveUsedShippingServiceConstraint $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof DPDTransport) {
            return;
        }

        if (!$value->getChannel() instanceof Channel) {
            return;
        }

        $upsShippingMethod = $this->integrationShippingMethodFactory->create($value->getChannel());
        $shippingMethodValidatorResult = $this->shippingMethodValidator->validate($upsShippingMethod);

        $this->handleValidationResult($shippingMethodValidatorResult);
    }

    private function handleValidationResult(ShippingMethodValidatorResultInterface $shippingMethodValidatorResult)
    {
        if (
            !isset($shippingMethodValidatorResult->getErrors()['errors'])
            || $shippingMethodValidatorResult->getErrors()['errors']->isEmpty()
        ) {
            return;
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        foreach ($shippingMethodValidatorResult->getErrors()['errors'] as $error) {
            $context->buildViolation($error->getMessage())
                ->setTranslationDomain(null)
                ->atPath(static::VIOLATION_PATH)
                ->addViolation();
        }
    }
}

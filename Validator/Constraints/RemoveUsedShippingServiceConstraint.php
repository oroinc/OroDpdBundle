<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating removal of used DPD shipping services.
 */
class RemoveUsedShippingServiceConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.dpd.transport.shipping_service.used';

    #[\Override]
    public function validatedBy(): string
    {
        return RemoveUsedShippingServiceValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

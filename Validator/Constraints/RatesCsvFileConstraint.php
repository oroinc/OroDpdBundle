<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RatesCsvFileConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.dpd.transport.rates_csv.invalid';

    #[\Override]
    public function validatedBy(): string
    {
        return RatesCsvFileValidator::ALIAS;
    }
}

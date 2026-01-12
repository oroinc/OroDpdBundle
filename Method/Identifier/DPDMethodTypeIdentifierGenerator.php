<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Generates identifiers for DPD shipping method types.
 */
class DPDMethodTypeIdentifierGenerator implements DPDMethodTypeIdentifierGeneratorInterface
{
    #[\Override]
    public function generateIdentifier(Channel $channel, ShippingService $service)
    {
        return $service->getCode();
    }
}

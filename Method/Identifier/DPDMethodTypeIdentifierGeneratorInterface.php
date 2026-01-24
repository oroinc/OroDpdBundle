<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Defines the contract for generating DPD shipping method type identifiers.
 */
interface DPDMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return string
     */
    public function generateIdentifier(Channel $channel, ShippingService $service);
}

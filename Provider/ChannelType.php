<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Provides DPD channel type configuration for integration.
 */
class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'dpd';

    #[\Override]
    public function getLabel()
    {
        return 'oro.dpd.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/orodpd/img/DPD_logo_icon.png';
    }
}

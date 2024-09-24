<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Oro\Bundle\DPDBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    private ChannelType $channel;

    #[\Override]
    protected function setUp(): void
    {
        $this->channel = new ChannelType();
    }

    public function testGetLabel()
    {
        self::assertInstanceOf(ChannelInterface::class, $this->channel);
        self::assertEquals('oro.dpd.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        self::assertInstanceOf(IconAwareIntegrationInterface::class, $this->channel);
        self::assertEquals('bundles/orodpd/img/DPD_logo_icon.png', $this->channel->getIcon());
    }
}

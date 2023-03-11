<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DPDBundle\DependencyInjection\OroDPDExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroDPDExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroDPDExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}

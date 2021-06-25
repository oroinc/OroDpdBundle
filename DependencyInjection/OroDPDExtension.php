<?php

namespace Oro\Bundle\DPDBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages DPDBundle service configuration
 */
class OroDPDExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('conditions.yml');
        $loader->load('form_types.yml');
        $loader->load('event_listeners.yml');
        $loader->load('order_attachment.yml');
        $loader->load('transaction.yml');
        $loader->load('controllers.yml');
    }
}

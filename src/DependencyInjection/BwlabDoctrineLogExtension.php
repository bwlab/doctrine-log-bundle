<?php

namespace Bwlab\DoctrineLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class BwlabDoctrineLogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $configuration = $this->getConfiguration($configs, $container);
        $processed_configuration = $this->processConfiguration($configuration, $configs);

        $emName = sprintf('doctrine.orm.%s_entity_manager', $processed_configuration['entity_manager']);
        $emReference = new Reference($emName);
        $definition = $container->register('bwlab_doctrine_log.event_listener.logger', $processed_configuration['listener_class']);

        $container->setParameter('bwlab_doctrine_log.entity_log_class', $processed_configuration['entity_log_class']);

        $definition->setArgument(0, $emReference);
        $definition->setArgument(4, $processed_configuration['ignore_properties']);
    }
}


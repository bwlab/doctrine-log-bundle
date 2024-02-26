<?php

namespace Bwlab\DoctrineLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class BwlabDoctrineLogExtension extends ConfigurableExtension
{

    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.yml');

        $container->setParameter('bwlab_doctrine_log.entity_log_class', $mergedConfig['entity_log_class'] ?? null);
        $emName = sprintf('doctrine.orm.%s_entity_manager', $mergedConfig['entity_manager']);
        $emReference = new Reference($emName);

        $container->getDefinition('bwlab_doctrine_log.event_listener.logger')
            ->setArgument(0, $emReference);
        //$definition = $container->register('bwlab_doctrine_log.event_listener.logger', $processed_configuration['listener_class']);
    }
}


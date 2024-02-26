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
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.yml');
        $configuration = $this->getConfiguration($configs, $container);
        $processed_configuration = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bwlab_doctrine_log.entity_log_class', $processed_configuration['entity_log_class']);

    }
}


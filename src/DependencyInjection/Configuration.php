<?php

namespace Bwlab\DoctrineLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
	    $treeBuilder = new TreeBuilder('bwlab_doctrine_log');
	    if (method_exists($treeBuilder, 'getRootNode')) {
		    $rootNode = $treeBuilder->getRootNode();
	    } else {
		    // for symfony/config 4.1 and older
		    $rootNode = $treeBuilder->root('bwlab_doctrine_log');
	    }

        $rootNode
            ->children()
                ->arrayNode('ignore_properties')->prototype('scalar')->end()
            ->end()
            ->scalarNode('entity_manager')
                ->defaultValue('default')
            ->end()
            ->scalarNode('listener_class')
                ->defaultValue('Bwlab\DoctrineLogBundle\EventListener\Logger')
            ->end()
            ->scalarNode('entity_log_class')
                ->defaultValue('Bwlab\DoctrineLogBundle\Entity\Log')
            ->end()
        ;

        return $treeBuilder;
    }
}

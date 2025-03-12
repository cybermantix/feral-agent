<?php

namespace Feral\Agent;

use Feral\Core\Process\Catalog\CatalogSource\CatalogSource;
use Feral\Core\Process\NodeCode\NodeCodeSource\NodeCodeSource;
use Feral\Core\Process\ProcessInterface;
use Feral\Core\Process\ProcessSource;
use Feral\Core\Process\Reader\DirectoryProcessReader;
use Feral\Agent\DependencyInjection\CollectTaggedServicesPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * The Feral symfony bundle provides the functionality to use Feral
 * inside of code in a symfony application. A Feral process can be
 * triggered from a controller, command, or any driver that runs
 * code with symfony.
 */
class FeralAgentBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('process')
                    ->children()
                        ->scalarNode('configuration_directory')->end()
                        ->arrayNode('included_sources')
                            ->info('Which default sources of node code and catalog nodes should be included.')
                            ->prototype('scalar')
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new CollectTaggedServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -9999);
    }

    /**
     * @param array $config
     * @param ContainerConfigurator $container
     * @param ContainerBuilder $builder
     * @return void
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // GET THE CORE DIRECTORY BY REFLECTION
        //......................................
        $coreReflected = new \ReflectionClass(ProcessInterface::class);
        $coreDirectory = \dirname($coreReflected->getFileName(), 2);
        $parameters = $container->parameters();
        $parameters->set('feral-core.directory', $coreDirectory);
        // GET THE SYMFONY BUNDLE LOCATION BY
        // REFLECTION
        //......................................
        $reflected = new \ReflectionObject($this);
        $directory = \dirname($reflected->getFileName(), 1);
        $resourceDirectory = $directory . DIRECTORY_SEPARATOR . 'Resources/config/';
        $parameters->set('feral-symfony.directory', $directory);

        $container->import($resourceDirectory . 'feral-symfony-services.yaml');
        $services = $container->services();
        $services->defaults()
            ->autowire()
            ->autoconfigure();

        if ($container->env() == 'dev') {
            $container->import($resourceDirectory . 'feral-symfony-dev-services.yaml');
        }

        // INCLUDED SOURCES OF INFORMATION
        //................................
        if (!empty($config['process']['included_sources'])) {
            if (in_array('tagged_nodecode_source', $config['process']['included_sources'])) {
                $services
                    ->set('nodecode.source.tagged', NodeCodeSource::class)
                    ->public()
                    ->args([tagged_iterator('feral.nodecode')])
                    ->tag('feral.nodecode_source');
            }

            if (in_array('tagged_catalog_source', $config['process']['included_sources'])) {
                $services
                    ->set('catalog.source.tagged', CatalogSource::class)
                    ->public()
                    ->args([tagged_iterator('feral.catalog_node')])
                    ->tag('feral.catalog_source');
            }

            if (in_array('tagged_process_source', $config['process']['included_sources'])) {
                $services
                    ->set('process.source.tagged', ProcessSource::class)
                    ->public()
                    ->args([tagged_iterator('feral.process')])
                    ->tag('feral.process_source');
            }
        }

/*
        // NODE CODE FACTORY
        $services
            ->set(NodeCodeFactory::class, NodeCodeFactory::class)
            ->public()
            ->args([tagged_iterator('feral.nodecode_source')]);

        // CATALOG
        $services
            ->set(Catalog::class, Catalog::class)
            ->public()
            ->args([tagged_iterator('feral.catalog_source')]);

        // PROCESS FACTORY
        $services
            ->set(ProcessFactory::class, ProcessFactory::class)
            ->public()
            ->args([tagged_iterator('feral.process_source')]);

        // PROCESS VALIDATOR
        $services
            ->set(ProcessValidator::class, ProcessValidator::class)
            ->public()
            ->args([tagged_iterator('feral.process_validator')]);

*/


        // IF THE FILE SOURCE IS CONFIGURED, ADD AS A SOURCE
        if (!empty($config['process']['configuration_directory'])) {
            $services
                ->set('process.source.directory', DirectoryProcessReader::class)
                ->public()
                ->args([$config['process']['configuration_directory']])
                ->tag('feral.process_source');
        }

    }
}
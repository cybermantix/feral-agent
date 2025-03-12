<?php

namespace Feral\Agent\DependencyInjection;

use Feral\Core\Process\Catalog\Catalog;
use Feral\Core\Process\NodeCode\NodeCodeFactory;
use Feral\Core\Process\ProcessFactory;
use Feral\Core\Process\Validator\ProcessValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CollectTaggedServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // LOAD NODE CODE
        //...............
        $nodeCodeSources = $container->findTaggedServiceIds('feral.nodecode_source');
        $nodeCodeSourceServices = [];
        foreach ($nodeCodeSources as $key => $source) {
            $nodeCodeSourceServices[] = $container->getDefinition($key);
        }
        $nodeCodeFactory = $container->getDefinition(NodeCodeFactory::class);
        $nodeCodeFactory->addArgument($nodeCodeSourceServices);

        // LOAD CATALOG NODES
        $catalogSources = $container->findTaggedServiceIds('feral.catalog_source');
        $catalogSourceServices = [];
        foreach ($catalogSources as $key => $source) {
            $catalogSourceServices[] = $container->getDefinition($key);
        }
        $catalog = $container->getDefinition(Catalog::class);
        $catalog->addArgument($catalogSourceServices);

        // LOAD PROCESSES
        $processSources = $container->findTaggedServiceIds('feral.process_source');
        $processSourceServices = [];
        foreach ($processSources as $key => $source) {
            $processSourceServices[] = $container->getDefinition($key);
        }
        $processFactory = $container->getDefinition(ProcessFactory::class);
        $processFactory->addArgument($processSourceServices);

        // LOAD PROCESS VALIDATORS
        $processValidatorSources = $container->findTaggedServiceIds('feral.process_validator');
        $processValidatorSourceServices = [];
        foreach ($processValidatorSources as $key => $source) {
            $processValidatorSourceServices[] = $container->getDefinition($key);
        }
        $processValidator = $container->getDefinition(ProcessValidator::class);
        $processValidator->addArgument($processValidatorSourceServices);
    }
}
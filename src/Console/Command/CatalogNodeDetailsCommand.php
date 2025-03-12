<?php

namespace Feral\Agent\Console\Command;

use DataObject\Configuration;
use Feral\Core\Process\Attributes\ConfigurationDescriptionInterface;
use Feral\Core\Process\Catalog\Catalog;
use Feral\Core\Process\NodeCode\NodeCodeFactory;
use Feral\Core\Process\Result\Description\ResultDescriptionInterface;
use Reepository\ConfigurationRepository;
use Symfony\Component\Console\Attribute as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Console\AsCommand(
    name: 'feral:details:catalog-node',
    description: 'List the configuration for a catalog node.'
)]
class CatalogNodeDetailsCommand extends Command
{

    public function __construct(
        protected Catalog $catalog,
        protected NodeCodeFactory $factory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nodes = $this->catalog->getCatalogNodes();
        foreach ($nodes as $node) {
            $this->writeNode($node->getKey(), $output);
        }
        return Command::SUCCESS;
    }

    protected function writeNode(string $key, OutputInterface $output) {
        $catalogNode = $this->catalog->getCatalogNode($key);

        $requiredConfiguration = [];
        $optionalConfiguration = [];
        $catalogSuppliedValues = [];
        $resultDescriptions = [];

        // NODE CONFIGURATION
        $nodeCode = $this->factory->getNodeCode($catalogNode->getNodeCodeKey());
        $nodeCodeReflection = new \ReflectionClass($nodeCode::class);
        $nodeCodeAttributes = $nodeCodeReflection->getAttributes();
        foreach ($nodeCodeAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (is_a($instance, ConfigurationDescriptionInterface::class)) {
                $defaultValue = $instance->getDefault();
                if (empty($defaultValue)) {
                    $requiredConfiguration[$instance->getKey()] = $instance;
                } else {
                    $optionalConfiguration[$instance->getKey()] = $instance;
                }
            } else if (is_a($instance, ResultDescriptionInterface::class)) {
                $resultDescriptions[$instance->getResult()] = $instance->getDescription();
            }
        }

        // CATALOG CONFIGURATION
        $catalogNodeReflection = new \ReflectionClass($catalogNode::class);
        $catalogAttributes = $catalogNodeReflection->getAttributes();
        foreach ($catalogAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (is_a($instance, ConfigurationDescriptionInterface::class)) {
                $defaultValue = $instance->getDefault();
                if (empty($defaultValue)) {
                    $requiredConfiguration[$instance->getKey()] = $instance;
                } else if(isset($requiredConfiguration[$instance->getKey()])) {
                    $optionalConfiguration[$instance->getKey()] = $instance;
                    unset($requiredConfiguration[$instance->getKey()]);
                } else {
                    $optionalConfiguration[$instance->getKey()] = $instance;
                }
            }
        }

        $configuration = $catalogNode->getConfiguration();
        if(!empty($configuration)) {
            foreach ($configuration as $key => $value) {
                if (isset($requiredConfiguration[$key])) {
                    $catalogSuppliedValues[$key] = $requiredConfiguration[$key];
                    unset($requiredConfiguration[$key]);
                } else if (isset($optionalConfiguration[$key])) {
                    $catalogSuppliedValues[$key] = $optionalConfiguration[$key];
                    unset($optionalConfiguration[$key]);
                }
            }
        }

        // DETAILS
        $output->writeln(sprintf(
            "<options=bold>Catalog Node '%s'</>\nKey: %s\nDescription:%s",
            $catalogNode->getName(),
            $catalogNode->getKey(),
            $catalogNode->getDescription()
        ));


        if(!empty($requiredConfiguration)) {
            $output->writeln("Required Configuration:");
            foreach ($requiredConfiguration as $key => $value) {
                $output->writeln(sprintf(
                        " - %s <info>(%s)</info> : <comment>%s</comment>",
                        $value->getName(),
                        $value->getKey(),
                        $value->getDescription())
                );
            }
        }

        if(!empty($optionalConfiguration)) {
            $output->writeln("Optional Configuration:");
            foreach ($optionalConfiguration as $key => $value) {
                $output->writeln(sprintf(
                        " - %s <info>(%s)</info> : <comment>%s</comment>",
                        $value->getName(),
                        $value->getKey(),
                        $value->getDescription())
                );
            }
        }

        if(!empty($catalogSuppliedValues)) {
            $output->writeln("Catalog Node Provided Configuration:");
            foreach ($catalogSuppliedValues as $key => $value) {
                $output->writeln(sprintf(
                        " - %s <info>(%s)</info> : <comment>%s</comment>",
                        $value->getName(),
                        $value->getKey(),
                        $value->getDescription())
                );
            }
        }

        if (!empty($resultDescriptions)) {
            $output->writeln("Results:");
            foreach ($resultDescriptions as $key => $value) {
                $output->writeln(sprintf(
                        " - %s : <comment>%s</comment>",
                        $key,
                        $value)
                );
            }
        }

        $output->writeln("");
    }
}
<?php

namespace Feral\Agent\Process\Prompt;

use Feral\Core\Process\Attributes\ConfigurationDescriptionInterface;
use Feral\Core\Process\Catalog\Catalog;
use Feral\Core\Process\NodeCode\NodeCodeFactory;
use Feral\Core\Process\Result\Description\ResultDescriptionInterface;
use Symfony\Component\Console\Command\Command;

class RenderPrompt
{
    protected string $output = '';

    public function __construct(
        protected Catalog $catalog,
        protected NodeCodeFactory $factory,
    ) {}

    public function render(string $instruction = ''): string
    {
        $this->output = '';
        $this->addPreamble($instruction);
        $this->addConfig();
        $nodes = $this->catalog->getCatalogNodes();
        foreach ($nodes as $node) {
            $this->renderCatalogNode($node->getKey());
        }
        return $this->output;
    }

    protected function addPreamble(string $instruction = ''): void
    {
        $this->writeln(<<<EOT
           Write a process configuration for the Feral CCF system using a set of
           catalog nodes as compute process nodes. The Feral CCF uses a JSON configuration
           for a process that requires the 'schema_version' property, a unique process 'key',
           the version of of the process configuration, any input context data in an object,
           and an array of nodes.
           
           {
              "schema_version": 1,
              "key": "ask_gbot",
              "version": 1,
              "context": {},
              "nodes": [
                 // ...put nodes here...
              ]
           }
               
               
           EOT);

        if (!empty($instruction)) {
            $this->writeln("Write a process with the following instruction: " . $instruction . "\n\n");
        }
    }

    /**
     *
     */
    protected function addConfig(): void
    {
        $this->writeln(<<<EOT
          A process configuration contains an array of nodes. Each node requires 'key' property 
          that identifies it and must be unique. The node must also reference a catalog key with
          the 'catalog_node_key' property. If any additional configuration is required then a
          key value object will be added to the 'configuration' property. The configuration property
          is optional unless there are required configuration from the catalog node. Each node must 
          include an 'edges' property that maps the result code of the node to the next node's key. The
          'description' property describes the purpose of the node and is optional.
          
          All process configurations must start with 'start' node. The edge of the start node will map
          the "ok" result with the next node to process. Here is an example:
          
           {
              "key": "start",
              "description": "The starting node",
              "catalog_node_key": "start",
              "configuration": {},
              "edges": {
                "ok": "next_node_key"
            }
           
           All process configurations must end with the stop node. Here is an example:
           
            {
              "key": "stop",
              "description": "Stop",
              "catalog_node_key": "stop",
              "configuration": {},
              "edges": {}
            }
           
          EOT);
    }

    protected function renderCatalogNode(string $key): void
    {
        $output = '';
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
                if (empty($defaultValue) && !$instance->isOptional()) {
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
        $this->writeln(sprintf(
            "Catalog Node '%s' - %s\nDescription:%s",
            $catalogNode->getKey(),
            $catalogNode->getName(),
            $catalogNode->getDescription()
        ));


        if(!empty($requiredConfiguration)) {
            $this->writeln("Required Configuration:");
            foreach ($requiredConfiguration as $key => $value) {
                $this->writeln(sprintf(
                        " - %s (%s) : %s",
                        $value->getName(),
                        $value->getKey(),
                        $value->getDescription())
                );
            }
        }

        if(!empty($optionalConfiguration)) {
            $this->writeln("Optional Configuration:");
            foreach ($optionalConfiguration as $key => $value) {
                $this->writeln(sprintf(
                        " - %s (%s) : %s",
                        $value->getName(),
                        $value->getKey(),
                        $value->getDescription())
                );
            }
        }

        if(!empty($catalogSuppliedValues)) {
            $this->writeln("Catalog Node Provided Configuration:");
            foreach ($catalogSuppliedValues as $key => $value) {
                $this->writeln(sprintf(
                        " - %s (%s) : %s",
                        $value->getName(),
                        $key,
                        $value->getDescription())
                );
            }
        }

        if (!empty($resultDescriptions)) {
            $this->writeln("Results:");
            foreach ($resultDescriptions as $key => $value) {
                $this->writeln(sprintf(
                        " - %s : %s",
                        $key,
                        $value)
                );
            }
        }

        $this->writeln("");
    }

    protected function writeln($str): void
    {
        $this->write($str . "\n");
    }

    protected function write($str): void
    {
        $this->output .= $str;
    }

}
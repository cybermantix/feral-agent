<?php

namespace Feral\Agent\Process\Process;

use Feral\Core\Process\Attributes\ConfigurationDescriptionInterface;
use Feral\Core\Process\Catalog\CatalogInterface;
use Feral\Core\Process\NodeCode\NodeCodeFactory;
use Feral\Core\Process\ProcessInterface;

class ConfigurationRequirementBuilder
{
    private array $subject;

    public function __construct(
        private readonly CatalogInterface $catalog,
        private readonly NodeCodeFactory $factory
    ){}

    public function init($subject = ['required' => [], 'optional' => []]): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function withProcess(ProcessInterface $process): self
    {
        $nodes = $process->getNodes();
        foreach ($nodes as $node) {
            $catalogNode = $this->catalog->getCatalogNode($node->getCatalogNodeKey());
            $nodeCode = $this->factory->getNodeCode($catalogNode->getNodeCodeKey());
            $reflectionClass = new \ReflectionClass($nodeCode);
            $attributes = $reflectionClass->getAttributes();
            $appliedConfiguration = array_merge($catalogNode->getConfiguration(), $node->getConfiguration());

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof ConfigurationDescriptionInterface) {
                    $key = $instance->getKey();
                    if (in_array($key, array_keys($appliedConfiguration))) {
                        continue;
                    }
                    $defaultValue = $instance->getDefault() ?? '';
                    $configData = [
                        'key' => $key,
                        'name' => $instance->getName(),
                        'description' => $instance->getDescription(),
                        'default' => $defaultValue
                    ];
                    if ($instance->isOptional()) {
                        $this->subject['optional'][$key] = $configData;
                    } else {
                        $this->subject['required'][$key] = $configData;
                    }
                }
            }
        }
        return $this;
    }


    public function build(): array
    {
        return $this->subject;
    }
}
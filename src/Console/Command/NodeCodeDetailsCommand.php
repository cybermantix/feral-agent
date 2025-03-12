<?php

namespace Feral\Agent\Console\Command;

use DataObject\Configuration;
use Feral\Core\Process\Attributes\ConfigurationDescriptionInterface;
use Feral\Core\Process\Catalog\Catalog;
use Feral\Core\Process\NodeCode\NodeCodeFactory;
use Reepository\ConfigurationRepository;
use ReflectionClass;
use Symfony\Component\Console\Attribute as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Console\AsCommand(
    name: 'feral:details:node-code',
    description: 'List the configuration for a node code.'
)]
class NodeCodeDetailsCommand extends Command
{

    public function __construct(
        protected Catalog $catalog,
        protected NodeCodeFactory $factory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'The Catalog Node Key?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        $nodeCode = $this->factory->getNodeCode($key);

        $configurationDescriptions = [];

        $output->writeln(sprintf(
            '<options=bold>Feral Node Code "%s" (%s) Details</>',
            $nodeCode->getName(),
            $nodeCode->getKey()
        ));

        // NODE CONFIGURATION
        $nodeCodeReflection = new ReflectionClass($nodeCode::class);
        $nodeCodeAttributes = $nodeCodeReflection->getAttributes();
        foreach ($nodeCodeAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (is_a($instance, ConfigurationDescriptionInterface::class)) {
                $configurationDescriptions[$instance->getKey()] = $instance;
            }
        }

        $output->writeln("\n<options=underscore>Node Code Configuration</>");
        if (empty($configurationDescriptions)) {
            $output->writeln(sprintf(' - %s <info>(%s)</info> : <comment>Contains no configuration items.</comment>', $catalogNode->getName(), $catalogNode->getKey()));
        } else {
            foreach ($configurationDescriptions as $description) {
                $output->writeln(sprintf(" - %s <info>(%s)</info> : <comment>%s</comment>", $description->getName(), $description->getKey(), $description->getDescription()));
            }
        }
        return Command::SUCCESS;
    }
}
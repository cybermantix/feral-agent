<?php

namespace Feral\Agent\Console\Command;

use DataObject\Configuration;
use Feral\Core\Process\ProcessFactory;
use Feral\Core\Process\ProcessInterface;
use Feral\Core\Process\Validator\ProcessValidator;
use Reepository\ConfigurationRepository;
use Symfony\Component\Console\Attribute as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Console\AsCommand(
    name: 'feral:list:processes',
    description: 'List all of the processes or use a filter to match processes.'
)]
class ListProcessesCommand extends Command
{

    public function __construct(
        private ProcessFactory $factory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::OPTIONAL, 'The Catalog Node Key?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('List the processes');
        $processes = $this->factory->getAllProcesses();
        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $output->writeln(sprintf(' - %s', $process->getKey()));
        }
        return Command::SUCCESS;
    }
}
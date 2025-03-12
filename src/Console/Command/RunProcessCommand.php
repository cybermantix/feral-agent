<?php

namespace Feral\Agent\Console\Command;

use DataObject\Configuration;
use Feral\Core\Process\Context\Context;
use Feral\Core\Process\Engine\ProcessEngine;
use Feral\Core\Process\ProcessFactory;
use Feral\Core\Process\ProcessJsonHydrator;
use Feral\Core\Process\Reader\DirectoryProcessReader;
use Feral\Core\Process\Validator\ProcessValidator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Reepository\ConfigurationRepository;
use Symfony\Component\Console\Attribute as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Console\AsCommand(
    name: 'feral:run',
    description: 'Run a process and pass in context data.'
)]
class RunProcessCommand extends Command
{
    const DEFAULT_CONTEXT = '{}';

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ProcessEngine $engine,
        protected ProcessFactory $factory,
        protected ProcessValidator $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('process', InputArgument::REQUIRED, 'The process key to process.');
        $this->addArgument('context', InputArgument::OPTIONAL, 'Data used to init the context. Use a path to a valid file or raw JSON in a string. Note if using linux command line, use backslash for keys. Example: "{\"test\": 10}"', self::DEFAULT_CONTEXT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // HELLO
        $output->writeln('Run Feral Run!');
        if ($output->isVeryVerbose()) {
            $output->writeln('<info>ABOUT:</info>', OutputInterface::VERBOSITY_VERY_VERBOSE);
            $output->writeln(
                "The <comment>Feral system</comment> is an open source application composition " .
                "framework allowing processes to be build and run for APIs and other applications. The <comment>Feral system</comment> " .
                "was built after a couple dozen years working with open source projects and constantly in need of " .
                "composition to manipulate data. If you are one of those who don't think a system like this should " .
                "exist in PHP, feel free to email <comment>gotohell@software-is-not-religion.com</comment>. " .
                "The <comment>Feral system</comment> was conceived in the warped brain of Gary Clift.", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $output->writeln("\n\n");
        }

        // PROCESS KEY
        $processKey = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $input->getArgument('process'));
        if ($output->isVerbose()) {
            $output->writeln(sprintf("<info>Process Key:</info> %s", $processKey));
        }


        // CONTEXT
        if ($output->isVerbose()) {
            $this->writeHeader($output, 'context');
        }
        $rawContext = $input->getArgument('context');
        if (is_file($rawContext)) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf("Using File <info>%s</info> for context", $rawContext));
            }
            $rawContext = file_get_contents($rawContext);
        }
        if ($output->isDebug()) {
            $output->writeln(
                sprintf("<info>RAW CONTEXT:</info>\n%s", $rawContext),
                OutputInterface::VERBOSITY_DEBUG);
        }
        $contextData = json_decode($rawContext, true);
        if (!empty($rawContext) && $rawContext != self::DEFAULT_CONTEXT && empty($contextData)) {
            $output->writeln(sprintf("<error>Invalid context</error>\n%s\nJSON Error Code: %s", $rawContext, $this->getErrorCode(json_last_error())));
            return Command::FAILURE;
        }
        if ($output->isDebug() && !empty($contextData)) {
            $output->writeln('<info>Context Data Entered: </info>');
            foreach ($contextData as $key => $value) {
                $output->writeln(sprintf(' <comment>%s:</comment> %s', $key, (string)$value));
            }
        } elseif ($output->isVeryVerbose() && !empty($contextData)) {
            $output->writeln(
                sprintf("<info>Context Keys Entered: </info>%s", implode(', ', array_keys($contextData))
                )
            );
        } elseif ($output->isVerbose()) {
            $output->write("<info>Context: </info> ");
            if (!empty($contextData)) {
                $output->writeln("was passed in.");
            } else {
                $output->writeln("was not passed in.");
            }
        }

        // CONTEXT
        $context = new Context();
        foreach ($contextData as $key => $value) {
            $context->set($key, $value);
        }

        // PROCESS
        if ($output->isVerbose()) {
            $this->writeHeader($output, 'process');
        }
        $process = $this->factory->build($processKey);


        // VALIDATE BEFORE RUNNING
        $errors = $this->validator->validate($process);
        if (!empty($errors)) {
            $output->writeln('ERRORS');
            foreach ($errors as $error) {
                $output->writeln(sprintf('<error>%s</error>', $error));
            }
            return Command::FAILURE;
        }

        $this->engine->process($process, $context);

        if ($output->isVerbose()) {
            $this->writeHeader($output, 'finalize');
        }
        if ($output->isDebug()) {
            $output->writeln(sprintf("<info>Final Context:</info>\n%s", print_r($context, true)));
        }
        $output->writeln(sprintf("Process '%s' Complete.\n\n", $processKey));
        return Command::SUCCESS;
    }

    /**
     * @param $output
     * @param $text
     * @return void
     */
    protected function writeHeader($output, $text): void
    {
        $headerPadding = 2;
        $dashes = str_repeat('-', strlen($text) + $headerPadding * 2);
        $output->writeln(sprintf("<comment>%s\n  %s\n%s", $dashes, strtoupper($text), $dashes));
    }

    /**
     * Convert the error code to human readable error
     * @param int $error
     * @return string
     */
    protected function getErrorCode(int $error): string
    {
        switch ($error) {
            case JSON_ERROR_NONE: return ' - No errors';
            case JSON_ERROR_DEPTH: return ' - Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH: return ' - Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR: return ' - Unexpected control character found';
            case JSON_ERROR_SYNTAX: return ' - Syntax error, malformed JSON';
            case JSON_ERROR_UTF8: return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            default: return ' - Unknown error';
        }
    }
}
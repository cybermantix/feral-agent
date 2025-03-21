<?php

namespace Feral\Agent\Process\Agent;

use Feral\Agent\Process\Agent\Brain\AgentBrainInterface;
use Feral\Agent\Process\Process\ConfigurationRequirementBuilder;
use Feral\Core\Process\Context\Context;
use Feral\Core\Process\Engine\ProcessEngine;
use Feral\Core\Process\ProcessFactory;
use Feral\Core\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * The process agent has a set of processes that can be run to complete
 * its mission.
 *
 */
class ProcessAgent implements AgentInterface
{
    const PROCESS_KEY = 'process_key';
    const PROCESS_CONTEXT = 'process_context';
    const PROCESS_REASON = 'process_reason';

    public function __construct(
        private readonly ProcessFactory $factory,
        private readonly ProcessEngine  $engine,
        private readonly AgentBrainInterface $brain,
        private readonly ConfigurationRequirementBuilder $builder,
        private readonly LoggerInterface $logger,
        private readonly Stopwatch $stopwatch,
    ) {}

    /**
     * @inheritDoc
     */
    function perform(string $mission, string $stimulus): AgentResult
    {
        $this->logger->debug(sprintf('Running ProcessAgent with "%s" and "%s"', $mission, $stimulus), ['event' => 'process_agent_start']);
        $this->stopwatch->start('perform');
        $promptInformation = [
            'command' => 'Take the mission and stimulus and decided which process should called and create the initial context. Required configuration values without default values must be set in the initial context. Optional configuration values are optional.',
            'mission' => $mission,
            'stimulus' => $stimulus,
            'processes' => $this->getProcessMeta(),
            'response' => $this->getOutputExample()
        ];


        $cognition = $this->think($promptInformation);
        $cognitionTiming = $this->stopwatch->lap('perform');
        $cognitionTime = $cognitionTiming->getDuration();

        // GET THE PROCESS SELECTED BY THE BRAIN
        if (empty($cognition[self::PROCESS_KEY])) {
            return AgentResult::INSUFFICIENT_PROCESSING_FAILURE;
        }
        $process = $this->factory->build($cognition[self::PROCESS_KEY]);

        // BUILD THE CONTEXT
        $context = new Context();
        foreach ($cognition[self::PROCESS_CONTEXT] as $key => $value) {
            $context->set($key, $value);
        }

        // PROCESS
        $this->engine->process($process, $context);
        $processTiming = $this->stopwatch->lap('perform');
        $processTime = $processTiming->getDuration() - $cognitionTime;
        $event = $this->stopwatch->stop('perform');
        $totalTime = $event->getDuration();

        $this->logger->info(
            sprintf('Execution times for ProcessAgent.process: total: %u ms, cognition: %u ms, process: %s ms',
                $totalTime,
                $cognitionTime,
                $processTime),
            ['event' => 'process_agent_end']
        );
        return AgentResult::SUCCESS;
    }

    protected function getProcessMeta(): array
    {

        $processes = $this->factory->getAllProcesses();
        $processMetaData = [];
        foreach ($processes as $process) {
            $configuration = $this->getConfiguration($process);
            $processMetaData[] = [
                'key' => $process->getKey(),
                'description' => $process->getDescription(),
                'required_context_values' => $configuration['required'],
                'optional_context_values' => $configuration['optional'],
            ];
        }
        return $processMetaData;
    }

    protected function getConfiguration(ProcessInterface $process): array
    {
        $configuration = $this->builder->init()
            ->withProcess($process)
            ->build();
        return $configuration;
    }

    protected function getOutputExample(): string
    {
        return sprintf(
            'Return JSON data with the suggested process key and initial context to run the process. {"%s": "my_key", "%s": {"one": 1, "customer": "ABC123"}, "%s": "The reason I chose this tool is ABC."}',
            self::PROCESS_KEY,
            self::PROCESS_CONTEXT,
            self::PROCESS_REASON,
        );
    }

    /**
     * @param array $promptInformation
     * @return array
     */
    protected function think(array $promptInformation): array
    {
        $promptTemplate = <<<EOT
            ### COMMAND
            %s
            
            ### MISSION
            %s
            
            ### STIMULUS
            %s
            
            ### PROCESS OPTIONS
            %s
            
            ### RESPONSE FORMAT
            %s
        EOT;

        $prompt = sprintf(
            $promptTemplate,
            $promptInformation['command'],
            $promptInformation['mission'],
            $promptInformation['stimulus'],
            json_encode($promptInformation['processes']),
            $promptInformation['response'],
        );

        $llmResponse = $this->brain->think($prompt);
        $content = $llmResponse['content'];

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $matches)) {
            $jsonString = $matches[0];
            $cognition = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(sprintf('JSON Error: %s', json_last_error()));
            }
        } else {
            throw new \Exception('No JSON Found in the response');
        }

        return $cognition;
    }
}
<?php

namespace Feral\Agent\Process\Agent;

use Feral\Agent\Process\Agent\Brain\AgentBrainInterface;
use Feral\Agent\Process\Process\ConfigurationRequirementBuilder;
use Feral\Agent\Process\Prompt\RenderPrompt;
use Feral\Core\Process\Context\Context;
use Feral\Core\Process\Engine\ProcessEngine;
use Feral\Core\Process\ProcessFactory;
use Feral\Core\Process\ProcessInterface;
use Feral\Core\Process\ProcessJsonHydrator;
use Feral\Core\Process\Validator\ProcessValidator;

/**
 * The process agent has a set of processes that can be run to complete
 * its mission.
 *
 */
class ProtoFlowAgent implements AgentInterface
{
    const PROCESS_KEY = 'process_key';
    const PROCESS_CONTEXT = 'process_context';
    const PROCESS_REASON = 'process_reason';

    public function __construct(
        private readonly ProcessFactory $factory,
        private readonly ProcessEngine  $engine,
        private readonly AgentBrainInterface $brain,
        private readonly ConfigurationRequirementBuilder $builder,
        private readonly RenderPrompt $renderPrompt,
        private readonly ProcessJsonHydrator $hydrator,
        private readonly ProcessValidator $validator,
    ) {}

    /**
     * @inheritDoc
     */
    function perform(string $mission, string $stimulus): AgentResult
    {
        $promptInformation = [
            'command' => 'Take the mission and stimulus and build a process and create the initial context. Required configuration values without default values must be set in the initial context. Optional configuration values are optional.',
            'mission' => $mission,
            'stimulus' => $stimulus,
            'process_builder' => $this->renderPrompt->render(),
            'response' => $this->getOutputExample()
        ];

        $cognition = $this->think($promptInformation);

        // GET THE PROCESS SELECTED BY THE BRAIN
        if (empty($cognition[self::PROCESS_KEY])) {
            return AgentResult::INSUFFICIENT_PROCESSING_FAILURE;
        }
        $process = $this->hydrator->hydrate(json_encode($cognition[self::PROCESS_KEY]));

        // VALIDATE
        $validationError = $this->validator->validate($process);
        if (!empty($validationError)) {
            return AgentResult::INSUFFICIENT_PROCESSING_FAILURE;
        }

        // BUILD THE CONTEXT
        $context = new Context();
        foreach ($cognition[self::PROCESS_CONTEXT] as $key => $value) {
            $context->set($key, $value);
        }

        // PROCESS
        $this->engine->process($process, $context);

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
            'Return JSON data with the suggested process key and initial context to run the process. {"%s": { "schema_version": 1, "key": "api_data_import", "version": 1, "context": {}, "nodes": [...]}, "%s": {"one": 1, "customer": "ABC123"}, "%s": "The reason I build this tool is ABC."}',
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
            
            ### PROCESS BUILDER INSTRUCTIONS
            %s
            
            ### RESPONSE FORMAT
            %s
        EOT;

        $prompt = sprintf(
            $promptTemplate,
            $promptInformation['command'],
            $promptInformation['mission'],
            $promptInformation['stimulus'],
            json_encode($promptInformation['process_builder']),
            $promptInformation['response'],
        );

        $llmResponse = $this->brain->think($prompt);
        $content = $llmResponse['content'];

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $matches)) {
            $jsonString = $matches[0];
            $cognition = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully decoded JSON
                print_r($cognition);
            } else {
                echo "Invalid JSON extracted.";
            }
        } else {
            echo "No JSON found in the response.";
        }

        return $cognition;
    }
}
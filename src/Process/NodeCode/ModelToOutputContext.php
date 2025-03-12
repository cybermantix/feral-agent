<?php

namespace Feral\Agent\NodeCode;

use Feral\Core\Process\Attributes\CatalogNodeDecorator;
use Feral\Core\Process\Attributes\StringArrayConfigurationDescription;
use Feral\Core\Process\Attributes\StringConfigurationDescription;
use Feral\Core\Process\Configuration\ConfigurationManager;
use Feral\Core\Process\Context\ContextInterface;
use Feral\Core\Process\NodeCode\Category\NodeCodeCategoryInterface;
use Feral\Core\Process\NodeCode\Traits\ConfigurationTrait;
use Feral\Core\Process\NodeCode\Traits\ConfigurationValueTrait;
use Feral\Core\Process\NodeCode\Traits\ContextMutationTrait;
use Feral\Core\Process\NodeCode\Traits\ContextValueTrait;
use Feral\Core\Process\NodeCode\Traits\NodeCodeMetaTrait;
use Feral\Core\Process\NodeCode\Traits\ResultsTrait;
use Feral\Core\Process\Result\ResultInterface;
use Feral\Core\Utility\Search\DataPathReader;
use Feral\Core\Utility\Search\DataPathReaderInterface;
use Feral\Core\Utility\Search\DataPathWriter;
use League\CommonMark\CommonMarkConverter;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Stratagi\Attributes\AiContext;

#[StringConfigurationDescription(
    key: self::MODEL_CONTEXT_PATH,
    name: 'Model Context Path',
    description: 'A context path where the FQCN of the model can be found.'
)]
#[StringConfigurationDescription(
    key: self::OUTPUT_CONTEXT_PATH,
    name: 'Output Context Path',
    description: 'The content path where the final prompt is stored.',
    default: 'prompt_output'
)]
#[StringConfigurationDescription(
    key: self::PREAMBLE_CONTEXT_PATH,
    name: 'Preamble Context Path',
    description: 'The content path where the prompt preamble is stored.'
)]
#[CatalogNodeDecorator(
    key:'model_to_output',
    name: 'Model to Output',
    group: 'Data',
    description: 'Take a model and create a GenAI prompt that will create the data in the right format.'
)]
#[CatalogNodeDecorator(
    key:'model_to_json',
    name: 'Model to JSON',
    group: 'Data',
    description: 'Take a model and create a GenAI prompt that will create the data in a JSON format.',
    configuration: [
        self::PREAMBLE_CONTEXT_PATH => 'Create a JSON structured response without comments to complete a PHP object with the following properties'
    ]
)]
class ModelToOutputContext implements \Feral\Core\Process\NodeCode\NodeCodeInterface
{

    use NodeCodeMetaTrait,
        ResultsTrait,
        ConfigurationTrait,
        ConfigurationValueTrait,
        ContextValueTrait,
        ContextMutationTrait;

    public function __construct(
        DataPathReaderInterface $dataPathReader = new DataPathReader(),
        DataPathWriter $dataPathWriter = new DataPathWriter(),
        ConfigurationManager $configurationManager = new ConfigurationManager()
    ) {
        $this->setMeta(
            self::KEY,
            self::NAME,
            self::DESCRIPTION,
            NodeCodeCategoryInterface::DATA
        )
            ->setConfigurationManager($configurationManager)
            ->setDataPathWriter($dataPathWriter)
            ->setDataPathReader($dataPathReader);
    }

    const KEY = 'model_to_output';

    const NAME = 'Model to Output';

    const DESCRIPTION = 'Convert a model to a GenAI prompt to inform of the output.';


    public const MODEL_CONTEXT_PATH = 'model_context_path';
    public const OUTPUT_CONTEXT_PATH = 'output_context_path';
    public const PREAMBLE_CONTEXT_PATH = 'preamble_context_path';

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context): ResultInterface
    {
        $fcqnContextPath = $this->getRequiredConfigurationValue(self::MODEL_CONTEXT_PATH);
        $outputPath = $this->getRequiredConfigurationValue(self::OUTPUT_CONTEXT_PATH);
        $preamble = $this->getRequiredConfigurationValue(self::PREAMBLE_CONTEXT_PATH);

        $fcqn = $this->getValueFromContext($fcqnContextPath, $context);
        if (!class_exists($fcqn)) {
            throw new \InvalidArgumentException("Class $fcqn does not exist.");
        }

        $reflectionClass = new \ReflectionClass($fcqn);

        if (!$reflectionClass) {
            throw new \Exception(sprintf('Cannot find class "%s"', $fcqn));
        }

        $prompt = [];
        $prompt[] = $preamble;
        $count = 0;
        foreach ($reflectionClass->getProperties() as $property) {
            $reflectionType = $property->getType();
            if ($reflectionType instanceof \ReflectionNamedType) {
                $type = $reflectionType->getName();
            } elseif ($reflectionType instanceof \ReflectionUnionType) {
                $typeNames = [];
                foreach ($reflectionType->getTypes() as $unionType) {
                    $typeNames[] = $unionType->getName();
                }
                $type = implode('|', $typeNames);
            } else {
                $type = 'mixed';
            }
            $attributes = $property->getAttributes(AiContext::class);
            $propertyDetails = [];
            foreach ($attributes as $attribute) {
                $count++;
                $aiContextInstance = $attribute->newInstance();
                $propertyDetails[] = "Property: " . $property->getName();
                $propertyDetails[] = "Type: " . $type;
                $propertyDetails[] = "Description: " . $aiContextInstance->description ?? '';
                $propertyDetails[] = "Intent: " . $aiContextInstance->intent ?? '';
                $propertyDetails[] = "Examples:\n  " . implode("\n  ", $aiContextInstance->examples ?? []);
                $propertyDetails[] = '';
            }
            if (!empty($propertyDetails)) {
                $prompt[] = implode("\n", $propertyDetails);
            }
        }

        $this->setValueInContext($outputPath, implode("\n", $prompt), $context);

        return $this->result(
            ResultInterface::OK,
            'Added the prompt to build the output for "%s" with %u properties',
            [$fcqn, $count]
        );
    }
}
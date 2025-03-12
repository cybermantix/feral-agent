<?php

namespace Feral\Agent\Process\NodeCode;

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
    key: self::INPUT_CONTEXT_PATH,
    name: 'Input Context Path',
    description: 'The context path where the data is located.'
)]
#[CatalogNodeDecorator(
    key:'hydrate_model',
    name: 'Hydrate Model',
    group: 'Data',
    description: 'Take data in the context and hydrate a model and put it in the context.'
)]
class HydrateModelWithResponse implements \Feral\Core\Process\NodeCode\NodeCodeInterface
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

    const KEY = 'hydrate_model';

    const NAME = 'Hydrate Model';

    const DESCRIPTION = 'Hydrate a model with data in the context.';


    public const MODEL_CONTEXT_PATH = 'model_context_path';
    public const OUTPUT_CONTEXT_PATH = 'output_context_path';
    public const INPUT_CONTEXT_PATH = 'input_context_path';

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context): ResultInterface
    {
        $fcqnContextPath = $this->getRequiredConfigurationValue(self::MODEL_CONTEXT_PATH);
        $outputPath = $this->getRequiredConfigurationValue(self::OUTPUT_CONTEXT_PATH);
        $inputContextPath = $this->getRequiredConfigurationValue(self::INPUT_CONTEXT_PATH);

        $fcqn = $this->getValueFromContext($fcqnContextPath, $context);
        if (!class_exists($fcqn)) {
            throw new \InvalidArgumentException("Class $fcqn does not exist.");
        }

        $object = new $fcqn();
        $reflectionClass = new \ReflectionClass($fcqn);

        $input = $this->getValueFromContext($inputContextPath, $context);
        $pattern = "/```json(.*?)```/s";
        if (preg_match($pattern, $input, $matches)) {
            $filteredInput = $matches[1];
        } else {
            throw new \Exception('Now JSON Data found.');
        }

        $filteredInput = preg_replace('#(?<!http:|https:)//.*|/\*[\s\S]*?\*/#', '', $filteredInput);
        $filteredInput = trim($filteredInput);

        $data = json_decode($filteredInput, true);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            $setterMethod = 'set' . ucfirst($propertyName);
            if (isset($data[$propertyName])) {
                $propertyType = $property->getType() ? $property->getType()->getName() : 'mixed';

                // Set the property value based on its type
                switch ($propertyType) {
                    case 'array':
                        $object->{$setterMethod}((array)$data[$propertyName]);
                        break;
                    case 'string':
                        if (is_array($data[$propertyName])) {
                            $data[$propertyName] = implode("\n\n", $data[$propertyName]);
                        }
                        $object->{$setterMethod}((string)$data[$propertyName]);
                        break;
                    case 'int':
                        if (is_array($data[$propertyName])) {
                            $data[$propertyName] = implode("\n\n", $data[$propertyName]);
                        }
                        $object->{$setterMethod}((int)$data[$propertyName]);
                        break;
                    case 'float':
                        if (is_array($data[$propertyName])) {
                            $data[$propertyName] = implode("\n\n", $data[$propertyName]);
                        }
                        $object->{$setterMethod}((float)$data[$propertyName]);
                        break;
                    case 'bool':
                        if (is_array($data[$propertyName])) {
                            $data[$propertyName] = implode("\n\n", $data[$propertyName]);
                        }
                        $object->{$setterMethod}((bool)$data[$propertyName]);
                        break;
                    default:
                        if (is_array($data[$propertyName])) {
                            $data[$propertyName] = implode("\n\n", $data[$propertyName]);
                        }
                        $object->{$setterMethod}($data[$propertyName]);
                }
            }
        }

        $this->setValueInContext($outputPath, $object, $context);

        return $this->result(
            ResultInterface::OK,
            'Added "%s" object',
            [$fcqn]
        );
    }
}
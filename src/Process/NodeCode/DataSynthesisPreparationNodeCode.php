<?php

namespace Feral\Agent\Process\NodeCode;

use Feral\Core\Process\Attributes\CatalogNodeDecorator;
use Feral\Core\Process\Attributes\StringArrayConfigurationDescription;
use Feral\Core\Process\Attributes\StringConfigurationDescription;
use Feral\Core\Process\Configuration\ConfigurationManager;
use Feral\Core\Process\Context\ContextInterface;
use Feral\Core\Process\NodeCode\Category\NodeCodeCategoryInterface;
use Feral\Core\Process\NodeCode\NodeCodeInterface;
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

#[StringArrayConfigurationDescription(
    key: self::INPUT_ARRAY_CONTEXT_PATH,
    name: 'Input Array',
    description: 'An array of context paths where the data can be found.'
)]
#[StringArrayConfigurationDescription(
    key: self::OUTPUT_CONTEXT_PATH,
    name: 'Output Context Path',
    description: 'The context path where the data will be stored.'
)]
#[CatalogNodeDecorator(
    key:'synthesis_prep',
    name: 'Synthesis Prep',
    group: 'GenAI',
    description: 'Merge data into a context value to be sent for synthesis.'
)]
class DataSynthesisPreparationNodeCode implements NodeCodeInterface
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

    const KEY = 'synthesis_prep';

    const NAME = 'Synthesis Preparation';

    const DESCRIPTION = 'Prepare the context to be sent to the synthesis node.';

    const HEADER = 'header';
    const DATA = 'data';

    public const INPUT_ARRAY_CONTEXT_PATH = 'input_array_context_path';
    public const OUTPUT_CONTEXT_PATH = 'output_context_path';
    public function process(ContextInterface $context): ResultInterface
    {
        $inputPaths = $this->getRequiredConfigurationValue(self::INPUT_ARRAY_CONTEXT_PATH);
        $outputPath = $this->getRequiredConfigurationValue(self::OUTPUT_CONTEXT_PATH);

        $inputData = [];
        $inputPathData = $this->getValueFromContext($inputPaths, $context);
        $outputData = (array) $this->getValueFromContext($outputPath, $context);
        foreach ($inputPathData as $path) {
            if (is_string($path)) {
                $inputData[] = $this->getValueFromContext($path, $context);
            } else {
                if (!empty($path[self::HEADER])) {
                    $inputData[] = $path[self::HEADER];
                }
                if (!empty($path[self::DATA])) {
                    $inputData[] = $this->getValueFromContext($path[self::DATA], $context);
                }
            }
        }

        $outputData[] = implode("\n", $inputData);
        $this->setValueInContext($outputPath, $outputData, $context);

        return $this->result(
            ResultInterface::OK,
            'Prepared %u inputs into "%s"',
            [count($inputData), $outputPath]
        );
    }

}
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

#[StringConfigurationDescription(
    key: self::INPUT_CONTEXT_PATH,
    name: 'Input Path',
    description: 'A context path where the data can be found.'
)]
#[StringConfigurationDescription(
    key: self::FILE_CONTEXT_PATH,
    name: 'File Context Path',
    description: 'The content path with the name of the file to be written.'
)]
#[CatalogNodeDecorator(
    key:'write_file',
    name: 'Write File',
    group: 'Data',
    description: 'Write a string in the context to a file.'
)]
class WriteFile implements \Feral\Core\Process\NodeCode\NodeCodeInterface
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

    const KEY = 'write_file';

    const NAME = 'Write File';

    const DESCRIPTION = 'Write a string found in the context to a file.';


    public const INPUT_CONTEXT_PATH = 'input_context_path';
    public const FILE_CONTEXT_PATH = 'file_context_path';

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context): ResultInterface
    {
        $inputPath = $this->getRequiredConfigurationValue(self::INPUT_CONTEXT_PATH);
        $filenamePath = $this->getRequiredConfigurationValue(self::FILE_CONTEXT_PATH);

        $data = $this->getValueFromContext($inputPath, $context);
        $filename = $this->getValueFromContext($filenamePath, $context);
        file_put_contents($filename, $data);
        return $this->result(
            ResultInterface::OK,
            'Wrote data to file "%s"',
            [$filename]
        );
    }
}
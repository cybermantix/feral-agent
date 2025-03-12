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
    key: self::OUTPUT_CONTEXT_PATH,
    name: 'Output Context Path',
    description: 'The context path where the data will be stored.',
    default: 'html_data'
)]
#[CatalogNodeDecorator(
    key:'convert_html',
    name: 'Convert To HTML',
    group: 'Data',
    description: 'Convert the markdown to HTML.'
)]
class GenerateHtmlFromMarkdown implements \Feral\Core\Process\NodeCode\NodeCodeInterface
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

    const KEY = 'convert_html';

    const NAME = 'Convert HTML';

    const DESCRIPTION = 'Convert Markdown to HTML.';

    public const INPUT_CONTEXT_PATH = 'input_context_path';
    public const OUTPUT_CONTEXT_PATH = 'output_context_path';

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context): ResultInterface
    {
        $inputPath = $this->getRequiredConfigurationValue(self::INPUT_CONTEXT_PATH);
        $outputPath = $this->getRequiredConfigurationValue(self::OUTPUT_CONTEXT_PATH);

        $markdown = $this->getValueFromContext($inputPath, $context);

        $converter = new CommonMarkConverter();
        $html = $converter->convert($markdown);

        $this->setValueInContext($outputPath, $html, $context);

        return $this->result(
            ResultInterface::OK,
            'Wrote HTML to context "%s"',
            [$outputPath]
        );
    }
}
<?php

namespace Feral\Agent\Process\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Feral\Agent\Process\DataCollector\Trace\ProcessTraceCollectorInterface;

class ProcessCollector extends AbstractDataCollector
{

    protected array $processTraces;

    /**
     * ProcessCollector constructor.
     */
    public function __construct(ProcessTraceCollectorInterface $processTraceCollector)
    {
        $this->processTraces = $processTraceCollector->getTraces();
    }


    public static function getTemplate(): ?string
    {
        return 'data_collector/process.html.twig';
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, Throwable $exception = null)
    {
        $this->data = [
            'process_traces' => $this->processTraces
        ];

    }

    public function getProcessTraces(): array
    {
        return $this->data['process_traces'];
    }
}

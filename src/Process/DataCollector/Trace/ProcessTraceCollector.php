<?php

namespace Feral\Agent\Process\DataCollector\Trace;


use Feral\Core\Process\Context\ContextInterface;
use Feral\Core\Process\Node\NodeInterface;
use Feral\Core\Process\ProcessInterface;
use Feral\Core\Process\Result\ResultInterface;

/**
 * Class ProcessTraceCollector
 * Collect traces during all the processes.
 * @package Feral\Agent\Process\DataCollector\Trace
 */
class ProcessTraceCollector implements ProcessTraceCollectorInterface
{

    /**
     * The current process currently being processed.
     * @var ProcessInterface
     */
    protected ProcessInterface $currentProcess;

    /**
     * The trace builder which create the trace.
     * @var ProcessTraceBuilder
     */
    protected ProcessTraceBuilder $builder;

    /**
     * Since multiple processes can be run as embedded processes
     * then we need to keep them separate until the end.
     * @var array
     */
    protected array $inProgressTraces = [];

    /**
     * Capture multiple traces.
     * @var array
     */
    protected array $processTraces = [];

    /**
     * The time the node started processing.
     * @var float
     */
    protected float $startTime;

    /**
     * The current node being processed.
     * @var NodeInterface
     */
    protected NodeInterface $currentNode;

    /**
     * @var string
     */
    protected string $processAlias;

    /**
     * ProcessTraceCollector constructor.
     */
    public function __construct()
    {
        $this->builder = new ProcessTraceBuilder();
    }

    /**
     * @inheritDoc
     */
    public function getTraces(): array
    {
        return $this->processTraces;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->processAlias = $alias;
    }

    /**
     * @inheritDoc
     */
    public function start(ProcessInterface $process, ContextInterface $context): void
    {
        $this->currentProcess = $process;
        $id = $this->getIdentity($context);
        $this->inProgressTraces[$id] = $this->builder
            ->init()
            ->withStartingContext($context)
            ->build();

        if (isset($this->processAlias)) {
            $this->builder->withAlias($this->processAlias);
        }
    }

    /**
     * @inheritDoc
     */
    public function end(ContextInterface $context): void
    {
        $id = $this->getIdentity($context);
        $this->builder
            ->init($this->inProgressTraces[$id])
            ->withEndingContext($context);
        $this->processTraces[] = $this->inProgressTraces[$id];
        $this->builder->clear();
        unset($this->inProgressTraces[$id]);
        unset($this->processAlias);
    }

    /**
     * @inheritDoc
     */
    public function startNode(NodeInterface $node, ContextInterface $context): void
    {
        $this->currentNode = $node;
        $this->startTime = microtime(true);
    }

    /**
     * @inheritDoc
     */
    public function endNode(ResultInterface $result, ContextInterface $context): void
    {
        $id = $this->getIdentity($context);
        $this->builder
            ->init($this->inProgressTraces[$id])
            ->generateTrace(
            $this->startTime,
            microtime(true),
            $this->currentNode,
            $context,
            $result
        );
    }

    protected function getIdentity(ContextInterface $context): string
    {
        return spl_object_hash($context);
    }
}

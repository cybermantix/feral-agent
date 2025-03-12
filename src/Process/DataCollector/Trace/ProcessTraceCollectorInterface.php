<?php


namespace Feral\Agent\Process\DataCollector\Trace;

use Feral\Core\Process\Context\ContextInterface;
use Feral\Core\Process\Node\NodeInterface;
use Feral\Core\Process\ProcessInterface;
use Feral\Core\Process\Result\ResultInterface;

/**
 * Interface ProcessTraceCollectorInterface
 * Collect information and data in the process.
 * @package Feral\Agent\Process\DataCollector\Trace
 */
interface ProcessTraceCollectorInterface
{
    /**
     * @return ProcessTrace[]
     */
    public function getTraces(): array;

    /**
     * Start a process.
     * @param ProcessInterface $process
     * @param ContextInterface $context
     */
    public function start(ProcessInterface $process, ContextInterface $context): void;

    /**
     * End a process
     * @param ContextInterface $context
     */
    public function end(ContextInterface $context): void;

    /**
     * Start a node
     * @param NodeInterface $node
     * @param ContextInterface $context
     */
    public function startNode(NodeInterface $node, ContextInterface $context): void;

    /**
     * End a node.
     * @param ResultInterface $result
     * @param ContextInterface $context
     */
    public function endNode(ResultInterface $result, ContextInterface $context): void;
}

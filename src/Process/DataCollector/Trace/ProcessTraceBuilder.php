<?php


namespace Feral\Agent\Process\DataCollector\Trace;

use Feral\Core\Process\Context\ContextInterface;
use Feral\Core\Process\Node\NodeInterface;
use Feral\Core\Process\Result\ResultInterface;

/**
 * Class ProcessTraceBuilder
 * Build the process trace object and easy building of the process run traces.
 * @package Feral\Agent\Process\DataCollector\Trace
 */
class ProcessTraceBuilder
{
    protected ProcessTrace $processTrace;

    /**
     * Init the builder with or without a process trace.
     * @param ProcessTrace|null $processTrace
     * @return $this
     */
    public function init(ProcessTrace $processTrace = null): self
    {
        if ($processTrace) {
            $this->processTrace = $processTrace;
        } else {
            $this->processTrace = new ProcessTrace();
        }
        return $this;
    }

    /**
     * Add the process alias
     * @param string $alias
     * @return $this
     */
    public function withAlias(string $alias): self
    {
        $this->processTrace->setAlias($alias);
        return $this;
    }

    /**
     * Add a snapshot of the starting context.
     * @param ContextInterface $context
     * @return $this
     */
    public function withStartingContext(ContextInterface $context): self
    {
        $data = $this->cloneContext($context->getAll());
        $this->processTrace->setStartingContextSnapshot($data);
        return $this;
    }

    /**
     * Add a snapshot of the ending context
     * @param ContextInterface $context
     * @return $this
     */
    public function withEndingContext(ContextInterface $context): self
    {
        $data = $this->cloneContext($context->getAll());
        $this->processTrace->setEndingContextSnapshot($data);
        return $this;
    }

    /**
     * Add a process run trace to the process trace.
     * @param ProcessRunTrace $processRunTrace
     * @return $this
     */
    public function withProcessRunTrace(ProcessRunTrace $processRunTrace): self
    {
        $this->processTrace->addProcessRunTraces($processRunTrace);
        return $this;
    }

    /**
     * Generate a new process run trace object using the parts of the node, context, and result.
     * @param float $start
     * @param float $end
     * @param NodeInterface $node
     * @param ContextInterface $context
     * @param ResultInterface $result
     * @return $this
     */
    public function generateTrace(
        float $start,
        float $end,
        NodeInterface $node,
        ContextInterface $context,
        ResultInterface $result
    ): self {
        $data = $this->cloneContext($context->getAll());
        $processRunTrace = (new ProcessRunTrace())
            ->setStart($start)
            ->setEnd($end)
            ->setContextSnapshot($data)
            ->setNodeKey($node->getKey())
            ->setConfiguration($node->getConfiguration())
            ->setResultCode($result->getStatus())
            ->setResultMessage($result->getMessage());
        $this->withProcessRunTrace($processRunTrace);
        return $this;
    }

    /**
     * Get the Process Trace.
     * @return ProcessTrace
     */
    public function build(): ProcessTrace
    {
        return $this->processTrace;
    }

    /**
     * Clear the current process.
     * @return $this
     */
    public function clear(): self
    {
        unset($this->processTrace);
        return $this;
    }

    /**
     * Make a clone of the context data.
     * @param $array
     * @return array
     */
    protected function cloneContext($array): array
    {
        return array_map(function($element) {
            return ((is_array($element))
                ? $this->cloneContext($element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            );
        }, $array);
    }
}

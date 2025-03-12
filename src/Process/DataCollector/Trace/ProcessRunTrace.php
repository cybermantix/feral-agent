<?php


namespace Feral\Agent\Process\DataCollector\Trace;

/**
 * Class ProcessRunTrace
 * Get the data about a node being run in a process.
 */
class ProcessRunTrace
{
    /**
     * The key of the node which was run
     * @var string
     */
    protected string $nodeKey;

    /**
     * The alias of the node used
     * @var string
     */
    protected string $nodeAlias = '';

    /**
     * When the node process started
     * @var float
     */
    protected float $start;

    /**
     * When the node process ended
     * @var float
     */
    protected float $end;

    /**
     * The configuration of the node.
     * @var array
     */
    protected array $configuration;

    /**
     * A snapshot of the context during this process
     * @var array
     */
    protected array $contextSnapshot;

    /**
     * The result code of this node run
     * @var string
     */
    protected string $resultCode;

    /**
     * The result message of the node run.
     * @var string
     */
    protected string $resultMessage;

    /**
     * @return string
     */
    public function getNodeKey(): string
    {
        return $this->nodeKey;
    }

    /**
     * @param string $nodeKey
     * @return ProcessRunTrace
     */
    public function setNodeKey(string $nodeKey): ProcessRunTrace
    {
        $this->nodeKey = $nodeKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getNodeAlias(): string
    {
        return $this->nodeAlias;
    }

    /**
     * @param string $nodeAlias
     * @return ProcessRunTrace
     */
    public function setNodeAlias(string $nodeAlias): ProcessRunTrace
    {
        $this->nodeAlias = $nodeAlias;
        return $this;
    }

    /**
     * @return float
     */
    public function getStart(): float
    {
        return $this->start;
    }

    /**
     * @param float $start
     * @return ProcessRunTrace
     */
    public function setStart(float $start): ProcessRunTrace
    {
        $this->start = $start;
        return $this;
    }

    /**
     * @return float
     */
    public function getEnd(): float
    {
        return $this->end;
    }

    /**
     * @param float $end
     * @return ProcessRunTrace
     */
    public function setEnd(float $end): ProcessRunTrace
    {
        $this->end = $end;
        return $this;
    }

    /**
     * Get the number of milliseconds it took to run
     * @return float
     */
    public function getRunTimeInMilliseconds(): float
    {
        return ($this->end - $this->start) * 1000;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     * @return ProcessRunTrace
     */
    public function setConfiguration(array $configuration): ProcessRunTrace
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return array
     */
    public function getContextSnapshot(): array
    {
        return $this->contextSnapshot;
    }

    /**
     * @param array $contextSnapshot
     * @return ProcessRunTrace
     */
    public function setContextSnapshot(array $contextSnapshot): ProcessRunTrace
    {
        foreach ($contextSnapshot as $key => $value) {
            if (is_object($value)) {
                $contextSnapshot[$key] = get_class($value);
            }
        }
        $this->contextSnapshot = $contextSnapshot;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultCode(): string
    {
        return $this->resultCode;
    }

    /**
     * @param string $resultCode
     * @return ProcessRunTrace
     */
    public function setResultCode(string $resultCode): ProcessRunTrace
    {
        $this->resultCode = $resultCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultMessage(): string
    {
        return $this->resultMessage;
    }

    /**
     * @param string $resultMessage
     * @return ProcessRunTrace
     */
    public function setResultMessage(string $resultMessage): ProcessRunTrace
    {
        $this->resultMessage = $resultMessage;
        return $this;
    }
}

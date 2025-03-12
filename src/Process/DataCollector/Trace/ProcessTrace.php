<?php


namespace Feral\Agent\Process\DataCollector\Trace;

class ProcessTrace
{
    /**
     * The alias of the process run
     * @var string
     */
    protected string $alias = '';

    /**
     * The initial context snapshot
     * @var array
     */
    protected array $startingContextSnapshot;

    /**
     * The final context snapshot
     * @var array
     */
    protected array $endingContextSnapshot;

    /**
     * The node process traces run.
     * @var ProcessRunTrace[]
     */
    protected array $processRunTraces = [];

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return ProcessTrace
     */
    public function setAlias(string $alias): ProcessTrace
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return array
     */
    public function getStartingContextSnapshot(): array
    {
        return $this->startingContextSnapshot;
    }

    /**
     * @param array $startingContextSnapshot
     * @return ProcessTrace
     */
    public function setStartingContextSnapshot(array $startingContextSnapshot): ProcessTrace
    {
        $this->startingContextSnapshot = $startingContextSnapshot;
        return $this;
    }

    /**
     * @return array
     */
    public function getEndingContextSnapshot(): array
    {
        return $this->endingContextSnapshot;
    }

    /**
     * @param array $endingContextSnapshot
     * @return ProcessTrace
     */
    public function setEndingContextSnapshot(array $endingContextSnapshot): ProcessTrace
    {
        $this->endingContextSnapshot = $endingContextSnapshot;
        return $this;
    }

    /**
     * @return ProcessRunTrace[]
     */
    public function getProcessRunTraces(): array
    {
        return $this->processRunTraces;
    }

    /**
     * @param ProcessRunTrace[] $processRunTraces
     * @return ProcessTrace
     */
    public function setProcessRunTraces(array $processRunTraces): ProcessTrace
    {
        $this->processRunTraces = $processRunTraces;
        return $this;
    }

    /**
     * @param ProcessRunTrace $processRunTrace
     * @return ProcessTrace
     */
    public function addProcessRunTraces(ProcessRunTrace $processRunTrace): ProcessTrace
    {
        $this->processRunTraces[] = $processRunTrace;
        return $this;
    }
}

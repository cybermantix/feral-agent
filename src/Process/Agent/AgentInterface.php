<?php

namespace Feral\Agent\Process\Agent;

enum AgentResult: int
{
    /** The agent was successful in performing the mission */
    case SUCCESS = 0;
    /** The agent failed to perform the mission */
    case FAILURE = 1;
    /** There are no Catalog nodes, processes, or other way to perform the mission. */
    case INSUFFICIENT_PROCESSING_FAILURE = 2;
}

interface AgentInterface
{
    const SUCCESS = 0;
    const FAILURE = 1;
    const INSUFFICIENT_PROCESSING_FAILURE = 2;

    /**
     * @param string $mission The desired outcome of the agent performing it's duty
     * @param string $stimulus The reason why this agent is performing it's duty
     * @return AgentResult 0 if OK, positive non-zero if not OK
     */
    function perform(string $mission, string $stimulus): AgentResult;
}
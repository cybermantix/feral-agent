<?php

namespace Feral\Agent\Process\Agent\Brain;

interface AgentBrainInterface
{
    function think(string $prompt): array;
}
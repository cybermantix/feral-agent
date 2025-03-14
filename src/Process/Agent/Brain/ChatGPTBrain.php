<?php

namespace Feral\Agent\Process\Agent\Brain;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatGPTBrain implements AgentBrainInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly string $apiKey
    ) {}

    public function think(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an AI assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();

            return $data['choices'][0]['message'] ?? [];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

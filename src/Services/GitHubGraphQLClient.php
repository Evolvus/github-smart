<?php

namespace App\Services;

use App\Config\AppConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Lightweight GraphQL client for the GitHub API.
 */
class GitHubGraphQLClient
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $config = AppConfig::getInstance();
        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.github.com/graphql',
            'headers' => [
                'Authorization' => 'Bearer ' . $config->get('github.token'),
                'User-Agent' => $config->get('app.name'),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30,
            'connect_timeout' => 10
        ]);
    }

    /**
     * Execute a GraphQL query and return the data portion of the response.
     *
     * @param string $query
     * @param array $variables
     * @throws GitHubGraphQLException
     */
    public function execute(string $query, array $variables = []): array
    {
        try {
            $response = $this->client->post('', [
                'json' => [
                    'query' => $query,
                    'variables' => $variables
                ]
            ]);

            $payload = json_decode($response->getBody()->getContents(), true);
            if (isset($payload['errors'])) {
                throw new GitHubGraphQLException('GraphQL query failed', $payload['errors']);
            }

            return $payload['data'] ?? [];
        } catch (RequestException $e) {
            throw new GitHubGraphQLException('GraphQL request failed: ' . $e->getMessage(), [], 0, $e);
        }
    }
}

class GitHubGraphQLException extends \Exception
{
    /** @var array<int, array<string,mixed>> */
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Return raw error array returned by the API.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

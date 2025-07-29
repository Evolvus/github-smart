<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Config\AppConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class GitHubService
{
    private Client $client;
    private AppConfig $config;
    private Logger $logger;

    public function __construct()
    {
        $this->config = AppConfig::getInstance();
        $this->setupClient();
        $this->setupLogger();
    }

    private function setupClient(): void
    {
        $this->client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get('github.token'),
                'User-Agent' => $this->config->get('app.name'),
                'Accept' => 'application/vnd.github.v3+json'
            ],
            'timeout' => 30,
            'connect_timeout' => 10
        ]);
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('github_service');
        $this->logger->pushHandler(new StreamHandler(
            $this->config->get('logging.file', 'app.log'),
            Logger::INFO
        ));
    }

    public function getOrganizationIssues(int $page = 1, int $perPage = 100): array
    {
        try {
            $org = $this->config->get('github.org');
            $response = $this->client->get("orgs/{$org}/issues", [
                'query' => [
                    'filter' => 'all',
                    'state' => 'all',
                    'per_page' => $perPage,
                    'page' => $page
                ]
            ]);

            $issues = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info("Retrieved issues from GitHub", [
                'org' => $org,
                'page' => $page,
                'count' => count($issues)
            ]);

            return $issues;
        } catch (RequestException $e) {
            $this->logger->error("Failed to retrieve issues from GitHub", [
                'org' => $this->config->get('github.org'),
                'error' => $e->getMessage(),
                'response_code' => $e->getResponse()?->getStatusCode()
            ]);
            throw new GitHubException("Failed to retrieve issues: " . $e->getMessage());
        }
    }

    public function getProjects(): array
    {
        try {
            $org = $this->config->get('github.org');
            $response = $this->client->post('graphql', [
                'json' => [
                    'query' => $this->getProjectsQuery($org)
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['errors'])) {
                $this->logger->warning("GraphQL API returned errors", [
                    'errors' => $data['errors']
                ]);
                return [];
            }

            return $this->parseProjectsResponse($data);
        } catch (RequestException $e) {
            $this->logger->warning("GraphQL API failed, falling back to REST", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getProjectsQuery(string $org): string
    {
        return <<<QUERY
        {
            organization(login: "{$org}") {
                projectsV2(first: 80) {
                    nodes {
                        id 
                        number
                        title
                        url
                        closed
                        items(first: 20) { 
                            nodes{
                                content { 
                                    ... on Issue { 
                                        id
                                        title 
                                        closed
                                        updatedAt
                                        labels(first: 10) {
                                            nodes {
                                                name
                                                color
                                            }
                                        }
                                    } 
                                }
                            }
                        }
                    }
                }
            }
        }
        QUERY;
    }

    private function parseProjectsResponse(array $data): array
    {
        $projects = [];
        
        if (!isset($data['data']['organization']['projectsV2']['nodes'])) {
            return $projects;
        }

        foreach ($data['data']['organization']['projectsV2']['nodes'] as $project) {
            if (empty($project) || $project['closed']) {
                continue;
            }

            foreach ($project['items']['nodes'] as $item) {
                if (empty($item) || empty($item['content']) || $item['content']['closed']) {
                    continue;
                }

                $issueId = $item['content']['id'];
                $projects[$issueId] = [
                    'id' => $project['id'],
                    'title' => $project['title'],
                    'closed' => $project['closed'],
                    'url' => $project['url']
                ];
            }
        }

        return $projects;
    }
}

class GitHubException extends \Exception
{
    // Custom GitHub API exception
} 
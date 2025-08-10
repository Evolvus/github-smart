<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Config\AppConfig;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class GitHubService
{
    private Client $client;
    private GitHubGraphQLClient $graphql;
    private AppConfig $config;
    private Logger $logger;

    public function __construct()
    {
        $this->config = AppConfig::getInstance();
        $this->setupClient();
        $this->graphql = new GitHubGraphQLClient();
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
                    'state' => 'open',
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
            
            // First, get all projects
            $data = $this->graphql->execute($this->getProjectsQuery($org));

            if (!isset($data['organization']['projectsV2']['nodes'])) {
                return [];
            }

            $projects = [];
            
            // For each project, fetch all items using cursor-based pagination
            foreach ($data['organization']['projectsV2']['nodes'] as $project) {
                if (empty($project) || $project['closed']) {
                    continue;
                }
                
                $projectId = $project['id'];
                $hasNextPage = true;
                $endCursor = null;
                
                while ($hasNextPage) {
                    try {
                        $itemsData = $this->graphql->execute(
                            $this->getProjectItemsQuery($projectId, $endCursor)
                        );
                    } catch (GitHubGraphQLException $e) {
                        $this->logger->warning(
                            'GraphQL API returned errors for project items',
                            [
                                'errors' => $e->getErrors(),
                                'project_id' => $projectId,
                            ]
                        );
                        break;
                    }

                    if (isset($itemsData['node']['items'])) {
                        $items = $itemsData['node']['items'];
                        $pageInfo = $items['pageInfo'];
                        
                        foreach ($items['nodes'] as $item) {
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
                        
                        $hasNextPage = $pageInfo['hasNextPage'];
                        $endCursor = $pageInfo['endCursor'];
                    } else {
                        $hasNextPage = false;
                    }
                }
            }

            return $projects;
        } catch (GitHubGraphQLException $e) {
            $this->logger->warning('GraphQL API failed', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
            return [];
        }
    }

    /**
     * Get project board status for all issues in all projects
     * This includes status field values for each issue in each project
     */
    public function getProjectBoardStatus(): array
    {
        try {
            $org = $this->config->get('github.org');
            
            // First, get all projects
            $data = $this->graphql->execute($this->getProjectsQuery($org));

            if (!isset($data['organization']['projectsV2']['nodes'])) {
                return [];
            }

            $projectStatuses = [];
            
            // For each project, fetch all items with their status field values
            foreach ($data['organization']['projectsV2']['nodes'] as $project) {
                if (empty($project) || $project['closed']) {
                    continue;
                }
                
                $projectId = $project['id'];
                $hasNextPage = true;
                $endCursor = null;
                
                while ($hasNextPage) {
                    try {
                        $itemsData = $this->graphql->execute(
                            $this->getProjectItemsWithStatusQuery($projectId, $endCursor)
                        );
                    } catch (GitHubGraphQLException $e) {
                        $this->logger->warning(
                            'GraphQL API returned errors for project items with status',
                            [
                                'errors' => $e->getErrors(),
                                'project_id' => $projectId,
                            ]
                        );
                        break;
                    }

                    if (isset($itemsData['node']['items'])) {
                        $items = $itemsData['node']['items'];
                        $pageInfo = $items['pageInfo'];
                        
                        foreach ($items['nodes'] as $item) {
                            if (empty($item) || empty($item['content']) || $item['content']['closed']) {
                                continue;
                            }
                            
                            $issueId = $item['content']['id'];
                            $itemId = $item['id'];
                            
                            // Extract status field values
                            $statusData = $this->extractStatusFieldValues($item['fieldValues']['nodes'] ?? []);
                            
                            $projectStatuses[$issueId] = [
                                'project_id' => $project['id'],
                                'project_title' => $project['title'],
                                'project_url' => $project['url'],
                                'item_id' => $itemId,
                                'status_fields' => $statusData
                            ];
                        }
                        
                        $hasNextPage = $pageInfo['hasNextPage'];
                        $endCursor = $pageInfo['endCursor'];
                    } else {
                        $hasNextPage = false;
                    }
                }
            }

            return $projectStatuses;
        } catch (GitHubGraphQLException $e) {
            $this->logger->warning('GraphQL API failed for project board status', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
            return [];
        }
    }

    /**
     * Extract status field values from project item field values
     */
    private function extractStatusFieldValues(array $fieldValues): array
    {
        $statusFields = [];
        
        foreach ($fieldValues as $fieldValue) {
            if (empty($fieldValue)) {
                continue;
            }
            
            // Handle different types of field values
            if (isset($fieldValue['field']['name'])) {
                $fieldName = $fieldValue['field']['name'];
                $fieldId = $fieldValue['field']['id'];
                
                // Handle status field (single select)
                if (isset($fieldValue['name'])) {
                    $statusFields[] = [
                        'field_id' => $fieldId,
                        'field_name' => $fieldName,
                        'value' => $fieldValue['name'],
                        'color' => $fieldValue['color'] ?? null
                    ];
                }
                // Handle text field
                elseif (isset($fieldValue['text'])) {
                    $statusFields[] = [
                        'field_id' => $fieldId,
                        'field_name' => $fieldName,
                        'value' => $fieldValue['text'],
                        'color' => null
                    ];
                }
                // Handle number field
                elseif (isset($fieldValue['number'])) {
                    $statusFields[] = [
                        'field_id' => $fieldId,
                        'field_name' => $fieldName,
                        'value' => (string)$fieldValue['number'],
                        'color' => null
                    ];
                }
                // Handle date field
                elseif (isset($fieldValue['date'])) {
                    $statusFields[] = [
                        'field_id' => $fieldId,
                        'field_name' => $fieldName,
                        'value' => $fieldValue['date'],
                        'color' => null
                    ];
                }
            }
        }
        
        return $statusFields;
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
                    }
                }
            }
        }
        QUERY;
    }

    private function getProjectItemsQuery(string $projectId, ?string $cursor = null): string
    {
        $afterClause = $cursor ? '"' . $cursor . '"' : 'null';
        return <<<QUERY
        {
            node(id: "{$projectId}") {
                ... on ProjectV2 {
                    items(first: 100, after: {$afterClause}) {
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                        nodes {
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
        QUERY;
    }

    private function getProjectItemsWithStatusQuery(string $projectId, ?string $cursor = null): string
    {
        $afterClause = $cursor ? '"' . $cursor . '"' : 'null';
        return <<<QUERY
        {
            node(id: "{$projectId}") {
                ... on ProjectV2 {
                    items(first: 100, after: {$afterClause}) {
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                        nodes {
                            id
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
                            fieldValues(first: 20) {
                                nodes {
                                    ... on ProjectV2ItemFieldSingleSelectValue {
                                        field {
                                            ... on ProjectV2SingleSelectField {
                                                id
                                                name
                                            }
                                        }
                                        name
                                        color
                                    }
                                    ... on ProjectV2ItemFieldTextValue {
                                        field {
                                            ... on ProjectV2Field {
                                                id
                                                name
                                            }
                                        }
                                        text
                                    }
                                    ... on ProjectV2ItemFieldNumberValue {
                                        field {
                                            ... on ProjectV2Field {
                                                id
                                                name
                                            }
                                        }
                                        number
                                    }
                                    ... on ProjectV2ItemFieldDateValue {
                                        field {
                                            ... on ProjectV2Field {
                                                id
                                                name
                                            }
                                        }
                                        date
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


}

class GitHubException extends \Exception
{
    // Custom GitHub API exception
} 
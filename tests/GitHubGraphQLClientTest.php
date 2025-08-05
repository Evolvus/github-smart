<?php

declare(strict_types=1);

namespace Tests;

use App\Services\GitHubGraphQLClient;
use App\Services\GitHubGraphQLException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GitHubGraphQLClientTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_NAME=TestApp');
        putenv('GITHUB_TOKEN=dummy');
    }

    public function testExecuteReturnsData(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => ['viewer' => ['login' => 'octocat']]]))
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $graphql = new GitHubGraphQLClient($client);

        $data = $graphql->execute('{ viewer { login } }');
        $this->assertSame('octocat', $data['viewer']['login']);
    }

    public function testExecuteThrowsOnError(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['errors' => [['message' => 'Bad request']]]))
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $graphql = new GitHubGraphQLClient($client);

        $this->expectException(GitHubGraphQLException::class);
        $graphql->execute('{ viewer { login } }');
    }
}

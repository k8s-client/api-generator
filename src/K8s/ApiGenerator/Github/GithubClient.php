<?php

/**
 * This file is part of the k8s/api-generator library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace K8s\ApiGenerator\Github;

use K8s\ApiGenerator\Exception\GithubException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubClient
{
    private const GITHUB_API_BASE = 'https://api.github.com';

    private HttpClientInterface $httpClient;

    public function __construct(?HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function getTags(string $owner, string $repo): GitTags
    {
        $gitTags = [];

        $response = $this->httpClient->request(
            'GET',
            $this->makeApiUri("/repos/{$owner}/{$repo}/git/refs/tags"),
            $this->makeHttpOptions()
        );
        $tags = json_decode($response->getContent(), true);

        foreach ($tags as $tag) {
            $gitTags[] = new GitTag($tag);
        }

        return new GitTags($gitTags);
    }

    public function getBlob(string $owner, string $repo, GitTag $tag, string $path): GitBlob
    {
        $pathInfo = pathinfo($path);
        $basePath = urlencode(ltrim($pathInfo['dirname'], '/'));

        $response = $this->httpClient->request(
            'GET',
            $this->makeApiUri("/repos/{$owner}/{$repo}/git/trees/{$tag->getCommonName()}:{$basePath}"),
            $this->makeHttpOptions()
        );
        $tree = json_decode($response->getContent(), true);

        $sha = null;
        foreach ($tree['tree'] as $branch) {
            if ($branch['path'] === $pathInfo['basename']) {
                $sha = $branch['sha'];
            }
        }

        if ($sha === null) {
            throw new GithubException(sprintf(
                'Unable to find the file in path "%s".',
                $path
            ));
        }

        $response = $this->httpClient->request(
            'GET',
            $this->makeApiUri("/repos/{$owner}/{$repo}/git/blobs/{$sha}"),
            $this->makeHttpOptions()
        );
        $content = json_decode($response->getContent(), true);

        return new GitBlob($content);
    }

    private function makeApiUri(string $path): string
    {
        return self::GITHUB_API_BASE . $path;
    }

    private function makeHttpOptions(): array
    {
        return [
            'headers' => [
                'User-Agent' => 'K8s API Generator',
            ],
        ];
    }
}

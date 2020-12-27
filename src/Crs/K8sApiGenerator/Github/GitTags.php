<?php

/**
 * This file is part of the crs/k8s-api-generator library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Crs\K8sApiGenerator\Github;

class GitTags
{
    private array $gitTags;

    public function __construct(array $gitTags)
    {
        $this->gitTags = $gitTags;
    }

    public function getLatestStableTag(?string $version = null): GitTag
    {
        $gitTags = $this->gitTags;

        # We first sort all tags, starting with the latest release
        usort($gitTags, function (GitTag $tag1, GitTag $tag2): int {
            return version_compare($tag1->getCommonName(), $tag2->getCommonName());
        });
        $gitTags = array_reverse($gitTags);

        # if no version is specified, we only want the latest stable release.
        # If no stable release is found, get the latest recorded.
        foreach ($gitTags as $tag) {
            /** @var GitTag $tag */
            if ($version === null && $tag->isStable()) {
                return $tag;
            } elseif ($version && $tag->startsWith($version) && $tag->isStable()) {
                return $tag;
            }
        }

        throw new \RuntimeException(sprintf(
            'Could not find a tag for version "%s".',
            $version
        ));
    }
}

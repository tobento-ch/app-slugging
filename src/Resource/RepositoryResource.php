<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Slugging\Resource;

use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Slugifier\ResourceInterface;
use Tobento\Service\Slugifier\Slug;
use Tobento\Service\Slugifier\SlugInterface;
use Closure;

class RepositoryResource implements ResourceInterface
{
    /**
     * Create a new RepositoryResource.
     *
     * @param RepositoryInterface $repository
     * @param null|Closure $whereParameters E.g. fn (string $slug, string $locale) => ['slug' => $slug]
     */
    public function __construct(
        protected RepositoryInterface $repository,
        protected null|string|Closure $resourceKey = null,
        protected null|Closure $resourceId = null,
        protected null|Closure $whereParameters = null,
        protected int $priority = 1000,
    ) {}
    
    /**
     * Returns true if the given slug exists, otherwise false.
     *
     * @param string $slug
     * @param string $locale
     * @return bool
     */
    public function slugExists(string $slug, string $locale = 'en'): bool
    {
        return (bool) $this->repository->count(where: $this->whereParameters($slug, $locale));
    }
    
    /**
     * Returns a single slug by the specified parameters or null if not found.
     *
     * @param string $slug
     * @param string $locale
     * @return null|SlugInterface
     */
    public function findSlug(string $slug, string $locale = 'en'): null|SlugInterface
    {
        $entity = $this->repository->findOne(where: $this->whereParameters($slug, $locale));
        
        if (is_null($entity)) {
            return null;
        }

        $key = $this->resourceKey instanceof Closure
            ? call_user_func($this->resourceKey, $entity)
            : $this->resourceKey;
        
        $id = is_null($this->resourceId)
            ? null
            : call_user_func($this->resourceId, $entity);
        
        return new Slug(
            slug: $slug,
            locale: $locale,
            resourceKey: $key,
            resourceId: $id,
        );
    }
    
    /**
     * Returns the key.
     *
     * @return null|string
     */
    public function key(): null|string
    {
        if ($this->resourceKey instanceof Closure) {
            return call_user_func($this->resourceKey, null);
        }
        
        return $this->resourceKey;
    }
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * Returns the resolved where parameters for the query.
     *
     * @param string $slug
     * @param string $locale
     * @return array
     */
    protected function whereParameters(string $slug, string $locale): array
    {
        if ($this->whereParameters instanceof Closure) {
            return call_user_func_array($this->whereParameters, [$slug, $locale]);
        }
        
        return ['slug' => $slug];
    }
}
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

namespace Tobento\App\Slugging\Routing;

use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Slugifier\SlugsInterface;

class SlugMatches
{
    /**
     * Create a new SlugMatches.
     *
     * @param string $resourceKey
     * @param null|string $withLocale
     * @param string $uriSlugName
     * @param null|string $withUriId
     */
    public function __construct(
        protected string $resourceKey,
        protected null|string $withLocale = null,
        protected string $uriSlugName = 'slug',
        protected null|string $withUriId = null,
    ) {}
    
    /**
     * Returns the route if the slug matches, otherwise null.
     *
     * @param SlugsInterface $slugs
     * @param RouteInterface $route
     * @return null|RouteInterface
     */
    public function __invoke(SlugsInterface $slugs, RouteInterface $route): null|RouteInterface
    {
        $slug = $route->getParameter('request_parameters')[$this->uriSlugName] ?? '';
        
        if ($slug === '') {
            return null;
        }
        
        // By default, we set an empty string, so it is locale independent and
        // let the handler be responsible because there may be locale fallbacks on an entity e.g.
        $locale = '';        
        
        if ($this->withLocale) {
            $locale = $route->getParameter('request_parameters')[$this->withLocale]
                ?? $route->getParameter(name: 'locale', default: '');
        }
        
        $slug = $slugs->findSlug(slug: $slug, locale: $locale);

        if (is_null($slug) || $slug->resourceKey() !== $this->resourceKey) {
           return null;
        }
        
        if ($this->withUriId) {
            $requestParams = $route->getParameter('request_parameters', []);
            $requestParams[$this->withUriId] = $slug->resourceId() ?: 0;
            unset($requestParams[$this->uriSlugName]);
            $route->parameter('request_parameters', $requestParams);            
        }

        return $route;
    }
}
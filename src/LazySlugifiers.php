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

namespace Tobento\App\Slugging;

use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Slugifier\Slugifiers;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifierFactoryInterface;

class LazySlugifiers extends Slugifiers
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new Modifiers.
     *
     * @param ContainerInterface $container
     * @param array<string, string|callable|SlugifierInterface|SlugifierFactoryInterface> $slugifiers
     */
    public function __construct(
        ContainerInterface $container,
        protected array $slugifiers = [],
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Returns a slugifier by name.
     *
     * @param string $name
     * @return SlugifierInterface
     */
    public function get(string $name): SlugifierInterface
    {
        if (!isset($this->slugifiers[$name])) {
            return $this->getFallbackSlugifier($name);
        }

        if ($this->slugifiers[$name] instanceof SlugifierInterface) {
            return $this->slugifiers[$name];
        }

        if ($this->slugifiers[$name] instanceof SlugifierFactoryInterface) {
            return $this->slugifiers[$name] = $this->slugifiers[$name]->createSlugifier();
        }
        
        if (is_string($this->slugifiers[$name])) {
            $this->slugifiers[$name] = $this->autowire->resolve($this->slugifiers[$name]);
            return $this->get($name);
        }
        
        // create slugifier from callable:
        if (!is_callable($this->slugifiers[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Unable to create slugifier %s as invalid type', $name)
            );
        }
        
        $this->slugifiers[$name] = $this->autowire->call($this->slugifiers[$name], ['name' => $name]);
        return $this->get($name);
    }
}
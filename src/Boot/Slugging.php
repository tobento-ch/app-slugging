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
 
namespace Tobento\App\Slugging\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Slugging\LazySlugifiers;
use Tobento\Service\Slugifier\SlugifierFactory;
use Tobento\Service\Slugifier\SlugifierFactoryInterface;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;
use Psr\Container\ContainerInterface;

class Slugging extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads slugging config file',
            'implements slugifier interfaces',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        \Tobento\App\Database\Boot\Database::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // install migration:
        $migration->install(\Tobento\App\Slugging\Migration\Slugging::class);
        
        // load config:
        $config = $config->load(file: 'slugging.php');
        
        // interfaces:
        $this->app->set(SlugifierFactoryInterface::class, SlugifierFactory::class);
        
        $this->app->set(
            SlugifiersInterface::class,
            static function(ContainerInterface $container) use ($config): SlugifiersInterface {
                return new LazySlugifiers(
                    container: $container,
                    slugifiers: $config['slugifiers'] ?? [],
                );
            }
        );
        
        $this->app->set(
            SlugifierInterface::class,
            static function(SlugifiersInterface $slugifiers): SlugifierInterface {
                return $slugifiers->get('default');
            }
        );
        
        // setting config interfaces:
        foreach($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
        
        // install slug repository migration after interfaces are set:
        $migration->install(\Tobento\App\Slugging\Migration\SlugRepository::class);
    }
}
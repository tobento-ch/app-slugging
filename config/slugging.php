<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\App\Slugging\SlugStorageRepository;
use Tobento\App\Slugging\Resource\RepositoryResource;
use Tobento\Service\Slugifier\SlugifierFactory;
use Tobento\Service\Slugifier\SlugifierFactoryInterface;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\Slugs;
use Tobento\Service\Slugifier\SlugsInterface;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\StorageInterface;

return [
    
    /*
    |--------------------------------------------------------------------------
    | Slugifiers
    |--------------------------------------------------------------------------
    |
    | Configure any slugifiers needed for your application.
    | The first slugifier is used as the default slugifier.
    |
    | See: https://github.com/tobento-ch/service-slugifier#creating-slugifier
    | See: https://github.com/tobento-ch/service-slugifier#creating-custom-slugifier
    |
    */
    
    'slugifiers' => [
        // using a factory:
        'default' => SlugifierFactory::class,
        
        // using closures:
        /*'default' => static function (SlugsInterface $slugs): SlugifierInterface|SlugifierFactoryInterface {
            return new SlugifierFactory(slugs: $slugs);
            // return new Slugifier(...);
        },*/
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names!
    |
    | See: https://github.com/tobento-ch/service-slugifier#slugs
    |
    */
    
    'interfaces' => [
        SlugsInterface::class => static function(SlugRepositoryInterface $slugRepository): SlugsInterface {
            $slugs = new Slugs();
            $slugs->addResource(new RepositoryResource(
                repository: $slugRepository,
                priority: 100, // higher priority will be first.
                resourceKey: static function (ItemInterface $entity): null|string {
                    return $entity->get('resource_key');
                },
                resourceId: static function (ItemInterface $entity): null|string|int {
                    return $entity->get('resource_id');
                },
                whereParameters: static function (string $slug, string $locale): array {
                    // add locale only if not empty as SlugMatches class sets an empty locale
                    // so it will be locale independent on routing.
                    return $locale === '' ? ['slug' => $slug] : ['slug' => $slug, 'locale' => $locale];
                },
            ));
            
            return $slugs;
        },
        
        SlugRepositoryInterface::class => static function(StorageInterface $storage): SlugRepositoryInterface {
            return new SlugStorageRepository(
                storage: $storage->new(),
                table: 'slugs',
            );
        },
    ],
];
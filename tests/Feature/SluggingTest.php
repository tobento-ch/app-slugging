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

namespace Tobento\App\Slugging\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Seeding\Repository\RepositoryFactory;
use Tobento\App\Slugging\Routing\SlugMatches;
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Slugifier\Resource\ArrayResource;
use Tobento\Service\Slugifier\SlugifierFactoryInterface;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;
use Tobento\Service\Slugifier\SlugsInterface;

class SluggingTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Slugging\Boot\Slugging::class);
        //$app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        return $app;
    }
    
    public function testInterfacesAreAvailable(): void
    {
        $app = $this->getApp();
        $app->booting();
        
        $this->assertInstanceof(SlugifierFactoryInterface::class, $app->get(SlugifierFactoryInterface::class));
        $this->assertInstanceof(SlugifierInterface::class, $app->get(SlugifierInterface::class));
        $this->assertInstanceof(SlugifiersInterface::class, $app->get(SlugifiersInterface::class));
        $this->assertInstanceof(SlugsInterface::class, $app->get(SlugsInterface::class));
        $this->assertInstanceof(SlugRepositoryInterface::class, $app->get(SlugRepositoryInterface::class));
    }
    
    public function testRouteMatchesWithMultipleArrayResources()
    {
        $booting = function ($app) {
            $app->on(SlugsInterface::class, function (SlugsInterface $slugs): void {
                $slugs->addResource(new ArrayResource(
                    slugs: ['about-cars'],
                    key: 'blog',
                ));
                $slugs->addResource(new ArrayResource(
                    slugs: ['red-pen'],
                    key: 'product',
                ));
            });

            $app->on(RouterInterface::class, function (RouterInterface $router): void {
                $router->get(
                    uri: '{slug}',
                    handler: function (string $slug) {
                        return ['resource' => 'blog', 'slug' => $slug];
                    },
                )->matches(new SlugMatches(resourceKey: 'blog'));

                $router->get(
                    uri: '{slug}',
                    handler: function (string $slug) {
                        return ['resource' => 'product', 'slug' => $slug];
                    },
                )->matches(new SlugMatches(resourceKey: 'product'));
            });
        };
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'about-cars');
        $booting($this->getApp());
        $http->response()->assertStatus(200)->assertBodySame('{"resource":"blog","slug":"about-cars"}');
        
        $http->request(method: 'GET', uri: 'red-pen');
        $booting($this->getApp());
        $http->response()->assertStatus(200)->assertBodySame('{"resource":"product","slug":"red-pen"}');
        
        $http->request(method: 'GET', uri: 'green-pen');
        $booting($this->getApp());
        $http->response()->assertStatus(404);
    }
    
    public function testRouteMatchesUsingIdWithArrayResource()
    {
        $booting = function ($app) {
            $app->on(SlugsInterface::class, function (SlugsInterface $slugs): void {
                $slugs->addResource(new ArrayResource(
                    slugs: ['about-cars'],
                    key: 'blog',
                ));
            });

            $app->on(RouterInterface::class, function (RouterInterface $router): void {
                $router->get(
                    uri: '{slug}',
                    handler: function (string $id) {
                        return ['resource' => 'blog', 'id' => $id];
                    },
                )->matches(new SlugMatches(resourceKey: 'blog', withUriId: 'id'));
            });
        };
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'about-cars');
        $booting($this->getApp());
        $http->response()->assertStatus(200)->assertBodySame('{"resource":"blog","id":"0"}');
    }
    
    public function testRouteMatchesWithSlugRepository()
    {
        $booting = function ($app) {
            $app->on(RouterInterface::class, function (RouterInterface $router): void {
                $router->get(
                    uri: '{slug}',
                    handler: function (string $slug) {
                        return ['resource' => 'blog', 'slug' => $slug];
                    },
                )->matches(new SlugMatches(resourceKey: 'blog'));
            });
        };
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'about-us');
        
        $booting($this->getApp());
        $app = $this->bootingApp();
        RepositoryFactory::new(
            repository: SlugRepositoryInterface::class,
            replaces: ['slug' => 'about-us', 'locale' => 'en', 'resource_key' => 'blog', 'resource_id' => 5],
        )->createOne();
        
        $http->response()->assertStatus(200)->assertBodySame('{"resource":"blog","slug":"about-us"}');
        
        $http->request(method: 'GET', uri: 'team');
        $booting($this->getApp());
        $http->response()->assertStatus(404);
    }
    
    public function testRouteMatchesWithEmptyUriSlugShouldNotMatchRoute()
    {
        $booting = function ($app) {
            $app->on(SlugsInterface::class, function (SlugsInterface $slugs): void {
                $slugs->addResource(new ArrayResource(
                    slugs: ['about-cars'],
                    key: 'blog',
                ));
            });

            $app->on(RouterInterface::class, function (RouterInterface $router): void {
                $router->get(
                    uri: '{slug}',
                    handler: function (string $id) {
                        return ['resource' => 'blog', 'id' => $id];
                    },
                )->matches(new SlugMatches(resourceKey: 'blog', withUriId: 'id'));
            });
        };
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        $booting($this->getApp());
        $http->response()->assertStatus(404);
    }
    
    public function testRouteMatchesWithSlugRepositoryShouldBeLocaleIndependent()
    {
        $booting = function ($app) {
            $app->on(RouterInterface::class, function (RouterInterface $router): void {
                $router->get(
                    uri: '{locale}/{slug}',
                    handler: function (string $slug) {
                        return ['resource' => 'blog', 'slug' => $slug];
                    },
                )->matches(new SlugMatches(resourceKey: 'blog'));
            });
        };
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'de/about-us');
        
        $booting($this->getApp());
        $app = $this->bootingApp();
        RepositoryFactory::new(
            repository: SlugRepositoryInterface::class,
            replaces: ['slug' => 'about-us', 'locale' => 'en', 'resource_key' => 'blog', 'resource_id' => 5],
        )->createOne();
        
        $http->response()->assertStatus(200)->assertBodySame('{"resource":"blog","slug":"about-us"}');
        
        $http->request(method: 'GET', uri: 'team');
        $booting($this->getApp());
        $http->response()->assertStatus(404);
    }
    
    public function testSlugRepositoryResourceShouldBeLocaleDependentOnSlugify()
    {
        $app = $this->bootingApp();        
        $slugifier = $app->get(SlugifierInterface::class);
        
        $this->assertSame('ueber-uns', $slugifier->slugify(string: 'über uns', locale: 'de'));
        
        RepositoryFactory::new(
            repository: SlugRepositoryInterface::class,
            replaces: ['slug' => 'ueber-uns', 'locale' => 'de'],
        )->createOne();
        
        $this->assertSame('ueber-uns', $slugifier->slugify(string: 'über uns', locale: 'de-CH'));
        
        RepositoryFactory::new(
            repository: SlugRepositoryInterface::class,
            replaces: ['slug' => 'ueber-uns', 'locale' => 'de-CH'],
        )->createOne();
        
        $this->assertSame('ueber-uns-1', $slugifier->slugify(string: 'über uns', locale: 'de-CH'));
    }
}
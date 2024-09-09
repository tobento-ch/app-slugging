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

namespace Tobento\App\Slugging\Test\Resource;

use PHPUnit\Framework\TestCase;
use Tobento\App\Slugging\Resource\RepositoryResource;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Slugifier\ResourceInterface;

class RepositoryResourceTest extends TestCase
{
    protected function createRepository(): RepositoryInterface
    {
        return new class(
            storage: new InMemoryStorage([
                'blog' => [
                    1 => ['id' => 1, 'slug' => 'paper'],
                    2 => ['id' => 2, 'slug' => 'pen'],
                ],
            ]),
            table: 'blog',
        ) extends StorageRepository {
            protected function configureColumns(): iterable|ColumnsInterface
            {
                return [
                    Column\Id::new(),
                    Column\Json::new('slug'),
                    Column\Text::new('locale', type: 'char')->type(length: 5),
                    Column\Text::new('resource_key'),
                    Column\Text::new('resource_id'),
                ];
            }
        };
    }
    
    protected function createRepositoryTranslated(): RepositoryInterface
    {
        return new class(
            storage: new InMemoryStorage([
                'blog' => [
                    1 => ['id' => 1, 'slug' => '{"en": "paper", "de": "papier"}'],
                    2 => ['id' => 2, 'slug' => '{"en": "pen", "de": "stift"}'],
                ],
            ]),
            table: 'blog',
        ) extends StorageRepository {
            protected function configureColumns(): iterable|ColumnsInterface
            {
                return [
                    Column\Id::new(),
                    Column\Json::new('slug'),
                    Column\Text::new('locale', type: 'char')->type(length: 5),
                    Column\Text::new('resource_key'),
                    Column\Text::new('resource_id'),
                ];
            }
        };
    }
    
    public function testThatImplementsResourceInterface()
    {
        $this->assertInstanceOf(
            ResourceInterface::class,
            new RepositoryResource(repository: $this->createRepository())
        );
    }

    public function testKeyMethod()
    {
        $resource = new RepositoryResource(repository: $this->createRepository());
        
        $this->assertSame(null, $resource->key());
    }

    public function testKeyMethodUsingString()
    {
        $resource = new RepositoryResource(
            repository: $this->createRepository(),
            resourceKey: 'blog',
        );
        
        $this->assertSame('blog', $resource->key());
    }
    
    public function testKeyMethodUsingClosure()
    {
        $resource = new RepositoryResource(
            repository: $this->createRepository(),
            resourceKey: static function (null|object $entity): null|string {
                return 'blog';
            },
        );
        
        $this->assertSame('blog', $resource->key());
    }
    
    public function testPriorityMethod()
    {
        $resource = new RepositoryResource(repository: $this->createRepository());
        $this->assertSame(1000, $resource->priority());
        
        $resource = new RepositoryResource(repository: $this->createRepository(), priority: 10);
        $this->assertSame(10, $resource->priority());
    }

    public function testSlugExistsMethod()
    {
        $r = new RepositoryResource(repository: $this->createRepository());
        
        $this->assertTrue($r->slugExists(slug: 'paper', locale: 'en'));
        $this->assertTrue($r->slugExists(slug: 'paper', locale: 'en-GB'));
        $this->assertTrue($r->slugExists(slug: 'paper', locale: 'de'));
    }
    
    public function testSlugExistsMethodUsingWhereParameters()
    {
        $r = new RepositoryResource(
            repository: $this->createRepositoryTranslated(),
            whereParameters: static function (string $slug, string $locale): array {
                return ['slug->'.$locale => $slug];
            },
        );
        
        $this->assertTrue($r->slugExists(slug: 'paper', locale: 'en'));
        $this->assertFalse($r->slugExists(slug: 'papier', locale: 'en'));
        $this->assertFalse($r->slugExists(slug: 'papier', locale: 'en-GB'));
        $this->assertTrue($r->slugExists(slug: 'papier', locale: 'de'));
        $this->assertFalse($r->slugExists(slug: 'paper', locale: 'de'));
    }
    
    public function testFindSlugMethod()
    {
        $r = new RepositoryResource(repository: $this->createRepository());
        $slug = $r->findSlug(slug: 'paper', locale: 'en');
        
        $this->assertSame('paper', $slug?->slug());
        $this->assertSame('en', $slug?->locale());
        $this->assertSame(null, $slug?->resourceKey());
        $this->assertSame(null, $slug?->resourceId());
        
        $this->assertNotNull($r->findSlug(slug: 'paper', locale: 'de'));
        $this->assertNotNull($r->findSlug(slug: 'paper', locale: 'de-CH'));
        $this->assertNotNull($r->findSlug(slug: 'paper', locale: 'en'));
        $this->assertNull($r->findSlug(slug: 'bar', locale: 'en'));
    }
    
    public function testFindSlugMethodUsingWhereParameters()
    {
        $r = new RepositoryResource(
            repository: $this->createRepositoryTranslated(),
            whereParameters: static function (string $slug, string $locale): array {
                return ['slug->'.$locale => $slug];
            },
        );
        
        $slug = $r->findSlug(slug: 'paper', locale: 'en');
        
        $this->assertSame('paper', $slug?->slug());
        $this->assertSame('en', $slug?->locale());
        $this->assertSame(null, $slug?->resourceKey());
        $this->assertSame(null, $slug?->resourceId());
        
        $this->assertNotNull($r->findSlug(slug: 'papier', locale: 'de'));
        $this->assertNull($r->findSlug(slug: 'paper', locale: 'de'));
        $this->assertNull($r->findSlug(slug: 'papier', locale: 'de-CH'));
        $this->assertNotNull($r->findSlug(slug: 'paper', locale: 'en'));
        $this->assertNull($r->findSlug(slug: 'papier', locale: 'en'));
        $this->assertNull($r->findSlug(slug: 'bar', locale: 'en'));
    }
    
    public function testFindSlugMethodUsingResourceKeyClosure()
    {
        $r = new RepositoryResource(
            repository: $this->createRepository(),
            resourceKey: static function (null|object $blog): null|string {
                return 'blog';
            },
        );
        
        $slug = $r->findSlug(slug: 'paper', locale: 'en');
        
        $this->assertSame('paper', $slug?->slug());
        $this->assertSame('en', $slug?->locale());
        $this->assertSame('blog', $slug?->resourceKey());
        $this->assertSame(null, $slug?->resourceId());
    }
    
    public function testFindSlugMethodUsingResourceIdClosure()
    {
        $r = new RepositoryResource(
            repository: $this->createRepository(),
            resourceId: static function (object $blog): null|int {
                return $blog->get('id');
            },
        );
        
        $slug = $r->findSlug(slug: 'paper', locale: 'en');
        
        $this->assertSame('paper', $slug?->slug());
        $this->assertSame('en', $slug?->locale());
        $this->assertSame(null, $slug?->resourceKey());
        $this->assertSame(1, $slug?->resourceId());
    }
}
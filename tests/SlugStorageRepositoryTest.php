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

namespace Tobento\App\Slugging\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\App\Slugging\SlugStorageRepository;
use Tobento\Service\Slugifier\Slug;
use Tobento\Service\Storage\InMemoryStorage;

class SlugStorageRepositoryTest extends TestCase
{
    public function testThatImplementsSlugRepositoryInterface()
    {
        $repo = new SlugStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'slugs',
        );
        
        $this->assertInstanceOf(SlugRepositoryInterface::class, $repo);
    }

    public function testSaveSlugMethod()
    {
        $repo = new SlugStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'slugs',
        );
        
        $slug = new Slug(
            slug: 'lorem-ipsum',
            locale: 'en',
            resourceKey: 'blog',
            resourceId: 125,
        );
        
        $savedSlug = $repo->saveSlug($slug);
        
        $this->assertSame($slug, $savedSlug);
    }
    
    public function testSaveSlugMethodInsertsIfNotExists()
    {
        $repo = new SlugStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'slugs',
        );
        
        $repo->saveSlug(new Slug(
            slug: 'lorem-ipsum',
            locale: 'en',
            resourceKey: 'blog',
            resourceId: 125,
        ));

        $repo->saveSlug(new Slug(
            slug: 'lorem-ipsum',
            locale: 'de',
            resourceKey: 'blog',
            resourceId: 125,
        ));
        
        $repo->saveSlug(new Slug(
            slug: 'lorem',
            locale: 'de',
            resourceKey: 'blog',
            resourceId: 230,
        ));  
        
        $this->assertSame(3, $repo->count());
    }
    
    public function testSaveSlugMethodUpdatesIfExists()
    {
        $repo = new SlugStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'slugs',
        );
        
        $repo->saveSlug(new Slug(
            slug: 'lorem-ipsum',
            locale: 'en',
            resourceKey: 'blog',
            resourceId: 125,
        ));

        $repo->saveSlug(new Slug(
            slug: 'lorem-ipsum',
            locale: 'en',
            resourceKey: 'blog',
            resourceId: 125,
        ));
        
        $this->assertSame(1, $repo->count());
    }
    
    public function testDeleteSlugMethod()
    {
        $repo = new SlugStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'slugs',
        );
        
        $slugEn = new Slug(
            slug: 'lorem-ipsum',
            locale: 'en',
            resourceKey: 'blog',
            resourceId: 125,
        );
        
        $slugDe = new Slug(
            slug: 'lorem-ipsum',
            locale: 'de',
            resourceKey: 'blog',
            resourceId: 125,
        );
        
        $repo->saveSlug($slugEn);
        $repo->saveSlug($slugDe);
        
        $this->assertSame(2, $repo->count());
        
        $deletedSlug = $repo->deleteSlug($slugEn);
        
        $this->assertSame(1, $repo->count());
        $this->assertSame($slugEn, $deletedSlug);
        
        $deletedSlug = $repo->deleteSlug($slugDe);
        
        $this->assertSame(0, $repo->count());
        $this->assertSame($slugDe, $deletedSlug);
    }
}
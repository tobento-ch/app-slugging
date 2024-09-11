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
use Tobento\App\Slugging\LazySlugifiers;
use Tobento\Service\Slugifier\SlugifierFactory;
use Tobento\Service\Slugifier\SlugifiersInterface;
use Tobento\Service\Slugifier\Slugs;
use Tobento\Service\Slugifier\SlugsInterface;
use Tobento\Service\Container\Container;

class LazySlugifiersTest extends TestCase
{
    public function testThatImplementsSlugifiersInterface()
    {
        $this->assertInstanceOf(SlugifiersInterface::class, new LazySlugifiers(new Container()));
    }
    
    public function testAddMethod()
    {
        $slugifiers = new LazySlugifiers(new Container());
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers->add(name: 'foo', slugifier: $slugifier);
        $slugifiers->add(name: 'bar', slugifier: new SlugifierFactory());
        
        $this->assertSame($slugifier, $slugifiers->get('foo'));
        $this->assertFalse($slugifiers->get('bar') === $slugifiers->get('foo'));
        $this->assertFalse($slugifiers->get('bar') === $slugifiers->get('baz'));
    }
    
    public function testHasMethod()
    {
        $slugifiers = new LazySlugifiers(new Container());
        $slugifiers->add(name: 'foo', slugifier: new SlugifierFactory());
        
        $this->assertTrue($slugifiers->has('foo'));
        $this->assertFalse($slugifiers->has('bar'));
    }
    
    public function testGetMethod()
    {
        $slugifiers = new LazySlugifiers(new Container());
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers->add(name: 'foo', slugifier: $slugifier);
        $slugifiers->add(name: 'bar', slugifier: (new SlugifierFactory())->createSlugifier());
        
        $this->assertSame($slugifier, $slugifiers->get('foo'));
        $this->assertFalse($slugifiers->get('foo') === $slugifiers->get('bar'));
    }
    
    public function testGetMethodFallsbackToFirstIfExists()
    {
        $slugifiers = new LazySlugifiers(new Container());
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers->add(name: 'first', slugifier: $slugifier);
        
        $this->assertSame($slugifier, $slugifiers->get('bar'));
    }
    
    public function testGetMethodThrowsExceptionIfInvalidSlugifierPassed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to create slugifier foo as invalid type');
        
        $slugifiers = new LazySlugifiers(
            container: new Container(),
            slugifiers: [
                'foo' => [],
            ],
        );
        
        $slugifiers->get('foo');
    }

    public function testNamesMethod()
    {
        $this->assertSame([], (new LazySlugifiers(new Container()))->names());
        
        $slugifiers = new LazySlugifiers(
            container: new Container(),
            slugifiers: [
                'foo' => new SlugifierFactory(),
            ],
        );

        $slugifiers->add(name: 'bar', slugifier: new SlugifierFactory());
        
        $this->assertSame(['foo', 'bar'], $slugifiers->names());
    }
    
    public function testSupportedSlugifierDefinitions()
    {
        $container = new Container();
        $container->set(SlugsInterface::class, new Slugs());
        
        $slugifiers = new LazySlugifiers(
            container: $container,
            slugifiers: [
                'factoryClass' => SlugifierFactory::class,
                'factoryInstance' => new SlugifierFactory(),
                'slugifierInstance' => (new SlugifierFactory())->createSlugifier(),
                'closureReturningSlugifierFactory' => function (SlugsInterface $slugs) {
                    return new SlugifierFactory(slugs: $slugs);
                },
                'closureReturningSlugifier' => function () {
                    return (new SlugifierFactory())->createSlugifier();
                },
            ],
        );
        
        $slugifiers->get('factoryClass');
        $slugifiers->get('factoryInstance');
        $slugifiers->get('slugifierInstance');
        $slugifiers->get('closureReturningSlugifierFactory');
        $slugifiers->get('closureReturningSlugifier');
        
        $this->assertTrue(true);
    }
}
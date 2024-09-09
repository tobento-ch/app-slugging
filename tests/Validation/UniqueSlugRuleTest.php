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

namespace Tobento\App\Slugging\Test\Validation;

use PHPUnit\Framework\TestCase;
use Tobento\App\Slugging\Validation\UniqueSlugRule;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Container\Container;
use Tobento\Service\Slugifier\Resource\ArrayResource;
use Tobento\Service\Slugifier\Slugs;
use Tobento\Service\Slugifier\SlugsInterface;
use Tobento\Service\Validation\RuleInterface;
use Tobento\Service\Validation\Validation;
use Tobento\Service\Validation\Rule\AutowireAware;
use Tobento\Service\Validation\Rule\ValidationAware;

class UniqueSlugRuleTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $rule = new UniqueSlugRule();
        
        $this->assertInstanceof(RuleInterface::class, $rule);
        $this->assertInstanceOf(AutowireAware::class, $rule);
        $this->assertInstanceOf(ValidationAware::class, $rule);
    }
    
    public function testPassesWithLocale()
    {
        $container = new Container();
        $container->set(SlugsInterface::class, function () {
            $slugs = new Slugs();
            $slugs->addResource(new ArrayResource(
                slugs: ['login'],
                supportedLocales: ['en*'],
            ));
            return $slugs;
        });
        
        $rule = new UniqueSlugRule(locale: 'en');
        $rule->setAutowire(new Autowire($container));
        
        $this->assertFalse($rule->passes('login'));
        $this->assertTrue($rule->passes('login-1'));
        
        $rule = new UniqueSlugRule(locale: 'de');
        $rule->setAutowire(new Autowire($container));
        
        $this->assertTrue($rule->passes('login'));
        $this->assertTrue($rule->passes('login-1'));
    }
    
    public function testPassesWithoutLocaleIsResolvedUsingValidationKey()
    {
        $container = new Container();
        $container->set(SlugsInterface::class, function () {
            $slugs = new Slugs();
            $slugs->addResource(new ArrayResource(
                slugs: ['login'],
                supportedLocales: ['en*'],
            ));
            return $slugs;
        });
        
        $rule = new UniqueSlugRule();
        $rule->setAutowire(new Autowire($container));
        $rule->setValidation(new Validation(rule: $rule, value: '', key: 'slug.en'));
        
        $this->assertFalse($rule->passes('login'));
        $this->assertTrue($rule->passes('login-1'));
        
        $rule = new UniqueSlugRule();
        $rule->setAutowire(new Autowire($container));
        $rule->setValidation(new Validation(rule: $rule, value: '', key: 'slug.de'));
        
        $this->assertTrue($rule->passes('login'));
        $this->assertTrue($rule->passes('login-1'));
    }
    
    public function testSkipValidationWithBoolTrueDoesSkip()
    {
        $container = new Container();
        $container->set(SlugsInterface::class, new Slugs());
        
        $rule = new UniqueSlugRule(locale: 'en', skipValidation: true);
        $rule->setAutowire(new Autowire($container));
        
        $this->assertTrue($rule->skipValidation('slug'));
    }
    
    public function testSkipValidationWithCallable()
    {
        $container = new Container();
        $container->set(SlugsInterface::class, new Slugs());
        
        $rule = new UniqueSlugRule(locale: 'en', skipValidation: function(mixed $value): bool {
            return true;
        });
        $rule->setAutowire(new Autowire($container));
        
        $this->assertTrue($rule->skipValidation('slug'));
    }

    public function testMessagesMethodWithErrorMessage()
    {
        $rule = new UniqueSlugRule(locale: 'en', errorMessage: 'Message');
        
        $this->assertSame('Message', $rule->messages()['passes'] ?? null);
    }
}
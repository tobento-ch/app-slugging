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

namespace Tobento\App\Slugging\Validation;

use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Slugifier\SlugsInterface;
use Tobento\Service\Validation\Rule\AutowireAware;
use Tobento\Service\Validation\Rule\HasAutowire;
use Tobento\Service\Validation\Rule\HasValidation;
use Tobento\Service\Validation\Rule\ValidationAware;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\Validation\Rule\Rule;
use Tobento\Service\Validation\ValidationInterface;

/**
 * UniqueSlugRule
 */
class UniqueSlugRule extends Rule implements AutowireAware, ValidationAware
{
    use HasAutowire;
    use HasValidation;
    
    /**
     * @var Passes
     */
    protected Passes $passes;
    
    /**
     * The error messages.
     */
    public const MESSAGES = [
        'passes' => 'The :attribute is not unique.',
    ];
    
    /**
     * Create a new UniqueSlugRule.
     *
     * @param null|string $locale
     * @param null|bool|callable $skipValidation
     * @param null|string $errorMessage
     */
    final public function __construct(
        protected null|string $locale = null,
        protected $skipValidation = null,
        protected null|string $errorMessage = null,
    ) {
        $this->passes = new Passes(
            passes: static function (
                string $value,
                ValidationInterface $validation,
                SlugsInterface $slugs,
            ) use ($locale) : bool {
                if (!is_string($locale)) {
                    $locale = static::resolveLocale($validation->key());
                }
                
                return ! $slugs->exists(slug: $value, locale: $locale);
            },
            skipValidation: $skipValidation,
            errorMessage: $errorMessage ?: static::MESSAGES['passes'],
        );
    }
    
    /**
     * Create a new instance.
     *
     * @param null|string $locale
     * @param null|bool|callable $skipValidation
     * @param null|string $errorMessage
     * @return static
     */
    public static function new(
        null|string $locale = null,
        $skipValidation = null,
        null|string $errorMessage = null,
    ): static {
        return new static($locale, $skipValidation, $errorMessage);
    }
    
    /**
     * Skips validation depending on value and rule method.
     * 
     * @param mixed $value The value to validate.
     * @param string $method
     * @return bool Returns true if skip validation, otherwise false.
     */
    public function skipValidation(mixed $value, string $method = 'passes'): bool
    {
        return $this->passes->skipValidation($value, $method);
    }
    
    /**
     * Determine if the validation rule passes.
     * 
     * @param mixed $value The value to validate.
     * @param array $parameters Any parameters used for the validation.
     * @return bool
     */
    public function passes(mixed $value, array $parameters = []): bool
    {
        return $this->passes->passes($value, $parameters);
    }
    
    /**
     * Returns the validation error messages.
     * 
     * @return array
     */
    public function messages(): array
    {
        return $this->passes->messages();
    }

    /**
     * Sets the autowire.
     * 
     * @param Autowire $autowire
     * @return static $this
     */
    public function setAutowire(Autowire $autowire): static
    {
        $this->passes->setAutowire($autowire);
        return $this;
    }
    
    /**
     * Sets the validation.
     * 
     * @param ValidationInterface $validation
     * @return static $this
     */
    public function setValidation(ValidationInterface $validation): static
    {
        $this->passes->setValidation($validation);
        return $this;
    }
    
    /**
     * Returns the resolved locale from the validation key.
     * 
     * @param null|string $key
     * @return string
     */
    public static function resolveLocale(null|string $key): string
    {
        if (is_null($key)) {
            return '';
        }
        
        if (!str_contains($key, '.')) {
            return '';
        }
        
        $parts = explode('.', $key);
        
        return $parts[array_key_last($parts)];
    }
}
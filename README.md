# App Slugging

Slugging support for the app using the [Slugifier Service](https://github.com/tobento-ch/service-slugifier).

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Slugging Boot](#slugging-boot)
        - [Slugging Config](#slugging-config)
    - [Generating Slugs](#generating-slugs)
    - [Adding Slugs](#adding-slugs)
        - [Repository Resource](#repository-resource)
    - [Slug Repository](#slug-repository)
    - [Routing](#routing)
        - [Slug Matches](#slug-matches)
    - [Slug Route Matcher](#slug-matches)
    - [Unique Slug Validation Rule](#unique-slug-validation-rule)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app slugging project running this command.

```
composer require tobento/app-slugging
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Slugging Boot

The slugging boot does the following:

* installs and loads slugging config file
* implements slugifier interfaces

```php
use Tobento\App\AppFactory;
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Slugifier\SlugifierFactoryInterface;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;
use Tobento\Service\Slugifier\SlugsInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Slugging\Boot\Slugging::class);
$app->booting();

// Implemented interfaces:
$slugifierFactory = $app->get(SlugifierFactoryInterface::class);
$slugifier = $app->get(SlugifierInterface::class);
$slugifiers = $app->get(SlugifiersInterface::class);
$slugs = $app->get(SlugsInterface::class);
$slugRepository = $app->get(SlugRepositoryInterface::class);

// Run the app
$app->run();
```

### Slugging Config

The configuration for the slugging is located in the ```app/config/slugging.php``` file at the default App Skeleton config location where you can specify the slugifiers for your application and more.

## Generating Slugs

To generate slugs use the slugifier interfaces:

```php
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;

class SomeService
{
    public function __construct(
        protected SlugifierInterface $slugifier,
        protected SlugifiersInterface $slugifiers,
    ) {}
    
    private function slugify()
    {
        // using the default slugifier:
        $slug = $this->slugifier->slugify(string: 'Lorem Ipsum!', locale: 'de');
        
        // using a custom slugifier:
        $slug = $this->slugifiers->get('custom')->slugify('Lorem Ipsum!');
    }
}
```

You may check out the [Slugifier Service](https://github.com/tobento-ch/service-slugifier) to learn more about it.

## Adding Slugs

You may add slugs to prevent dublicate slugs or for routing purposes such as using the [Slug Matches](#slug-matches) on routes.

**From Config**

You may add slugs using resources directly in the [Slugging Config](#slugging-config).

**Using The App**

Sometimes, it may be useful to add slugs using resources within the app:

```php
use Tobento\Service\Slugifier\Resource\ArrayResource;
use Tobento\Service\Slugifier\SlugsInterface;

// Adding slugs resources only if requested:
$app->on(SlugsInterface::class, static function(SlugsInterface $slugs): void {
    $slugs->addResource(new ArrayResource(
        slugs: ['login'],
    ));
});
```

### Repository Resource

With the ```RepositoryResource``` class you can add any repository implementing the ```RepositoryInterface``` as a resource.

```php
use Tobento\App\Slugging\Resource\RepositoryResource;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Slugifier\SlugsInterface;

// Adding slugs resources only if requested:
$app->on(SlugsInterface::class, static function(SlugsInterface $slugs, BlogRepositoryInterface $blogRepo): void {
    $slugs->addResource(new RepositoryResource(
        repository: $blogRepo,
        priority: 100, // higher priority will be first.
        
        resourceKey: 'blog', // or null
        // or using a closure:
        resourceKey: static function (null|object $blog): null|string {
            return $blog->resourceKey();
        },
        
        resourceId: static function (object $blog): null|string|int {
            return $blog->id();
        },
        // or null if none:
        resourceId: null,
        
        // you may customize the where query parameters:
        whereParameters: static function (string $slug, string $locale): array {
            return $locale === ''
                ? ['slug' => $slug] // locale independent (default)
                : ['slug' => $slug, 'locale' => $locale]; // locale dependent
            
            // JSON SYNTAX:
            return ['slug->'.$locale => $slug]; // locale dependent
        },
    ));
});
```

## Slug Repository

By default, the slug repository is [added to the slugs](#adding-slugs) in the [Slugging Config](#slugging-config) whereby preventing dublicated slugs.

The advantage using the slug repository is that there will be just one query while [generating slugs](#generating-slugs) or when using the [Slug Matches](#slug-matches) if it is the only [added slug resource](#adding-slugs).

**Saving Slugs**

Use the ```saveSlug``` method to save a slug:

```php
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Slugifier\Slug;

$slugRepository = $app->get(SlugRepositoryInterface::class);

$savedSlug = $slugRepository->saveSlug(new Slug(
    slug: 'lorem-ipsum',
    locale: 'en',
    resourceKey: 'blog', // null|string
    resourceId: 125, // null|int|string
));
```

**Deleting Slugs**

Use the ```deleteSlug``` method to delete a slug:

```php
use Tobento\App\Slugging\SlugRepositoryInterface;
use Tobento\Service\Slugifier\Slug;

$slugRepository = $app->get(SlugRepositoryInterface::class);

$deletedSlug = $slugRepository->deleteSlug(new Slug(
    slug: 'lorem-ipsum',
    locale: 'en',
));
```

## Routing

First, you will need to install the [App Http](https://github.com/tobento-ch/app-http).

### Slug Matches

You may use the ```SlugMatches``` class to have mutliple routes with a slug only uri matching different controllers based on the ```resouceKey``` parameter.

```php
use Tobento\App\Slugging\Routing\SlugMatches;

$app->route(
    method: 'GET',
    uri: '{slug}',
    //handler: [BlogController::class, 'show'],
    handler: function (string $slug) {
        return $createdResponse;
    },
)->matches(new SlugMatches(resourceKey: 'blog'));

$app->route(
    method: 'GET',
    uri: '{slug}',
    //handler: [ProductController::class, 'show'],
    handler: function (string $slug) {
        return $createdResponse;
    },
)->matches(new SlugMatches(resourceKey: 'product'));
```

**Using Locale**

You may use the ```withLocale``` parameter to define the name of the uri locale parameter. Once defined, slugs will be matched locale dependent.

```php
use Tobento\App\Slugging\Routing\SlugMatches;

$app->route(
    method: 'GET',
    uri: '{?locale}/{slug}',
    handler: function (string $slug) {
        return $createdResponse;
    },
)
->locales(['de', 'en'])
->localeOmit('en')
->matches(new SlugMatches(
    resourceKey: 'blog',
    withLocale: 'locale',
));
```

**Using The Resource Id**

You may use the ```withUriId``` parameter to define the name of the parameter passed to the handler whereby the resource id from the slug entity ```$slug->resourceId()``` will be passed.

```php
use Tobento\App\Slugging\Routing\SlugMatches;

$app->route(
    method: 'GET',
    uri: '{slug}',
    handler: function (int|string $id) {
        return $createdResponse;
    },
)->matches(new SlugMatches(
    resourceKey: 'blog',
    withUriId: 'id',
));
```

**Custom Slug Uri**

You may use the ```uriSlugName``` parameter to change the uri name of the slug.

```php
use Tobento\App\Slugging\Routing\SlugMatches;

$app->route(
    method: 'GET',
    uri: '{alias}',
    handler: function (string $alias) {
        return $createdResponse;
    },
)->matches(new SlugMatches(
    resourceKey: 'blog',
    uriSlugName: 'alias',
));
```

## Unique Slug Validation Rule

**Requirements**

It requires the [App Validation](https://github.com/tobento-ch/app-validation):

```
composer require tobento/app-validation
```

Do not forget to boot the validator:

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Validation\Boot\Validator::class);
$app->boot(\Tobento\App\Slugging\Boot\Slugging::class);

// Run the app
$app->run();
```

**Unique Slug Rule**

```php
use Tobento\App\Slugging\Validation\UniqueSlugRule;

$validation = $validator->validate(
    data: [
        'slug' => 'login',
        'slug.de' => 'anmelden',
        'slug.en' => 'login',
    ],
    rules: [
        'slug' => [
            new UniqueSlugRule(
                locale: 'en',
                // you may specify a custom error message:
                errorMessage: 'Custom error message',
            ),
        ],
        'slug.de' => [
            new UniqueSlugRule(), // locale is automatically determined as 'de'.
        ],
        'slug.en' => [
            new UniqueSlugRule(), // locale is automatically determined as 'en'.
        ],
    ]
);
```

**Skip validation**

You may use the ```skipValidation``` parameter in order to skip validation under certain conditions:

```php
use Tobento\App\Slugging\Validation\UniqueSlugRule;

$validation = $validator->validate(
    data: [
        'slug.en' => 'login',
    ],
    rules: [
        'slug.en' => [
            // skips validation:
            new UniqueSlugRule(skipValidation: true),

            // does not skip validation:
            new UniqueSlugRule(skipValidation: false),

            // skips validation:
            new UniqueSlugRule(skipValidation: fn (mixed $value): bool => $value === 'foo'),
        ],
    ]
);
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
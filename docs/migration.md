# Migration Notes

This document is for applications and agents migrating to the current
`tomvdpeet/breadcrumb-bundle` from either:

- `apy/breadcrumbtrail-bundle`
- the `1.x`, `main`, or `master` branch of this bundle

It focuses on practical code changes and behavior differences that matter while
porting existing breadcrumb attributes, PHP usage, Twig usage, and bundle
configuration.

## Platform requirements

The 2.x branch is a Symfony 8 and PHP 8.4 baseline.

Update your application before upgrading the bundle:

- PHP must be `>=8.4`.
- Symfony packages must be compatible with `^8.0`.
- Twig must be compatible with `^3.0`.
- The bundle now requires `symfony/yaml`.
- The Symfony PHPUnit Bridge is no longer used by this repository; the test
  script now runs `vendor/bin/phpunit --testdox`.

Symfony 8 route examples should use `Symfony\Component\Routing\Attribute\Route`
instead of the older `Symfony\Component\Routing\Annotation\Route` import.

## Bundle class and namespace

The bundle moved from the old `APY\BreadcrumbTrailBundle` namespace to
`TomvdPeet\BreadcrumbBundle`.

Update bundle registration when it is registered manually:

```php
// before
new APY\BreadcrumbTrailBundle\APYBreadcrumbTrailBundle(),

// after
new TomvdPeet\BreadcrumbBundle\TomvdPeetBreadcrumbBundle(),
```

Update PHP imports throughout your application:

```php
// before
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use APY\BreadcrumbTrailBundle\Annotation\ResetBreadcrumbTrail;
use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;

// after
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
```

Other public classes moved in the same way, for example:

- `APY\BreadcrumbTrailBundle\BreadcrumbTrail\Breadcrumb` became
  `TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Breadcrumb`.
- `APY\BreadcrumbTrailBundle\Twig\BreadcrumbTrailExtension` became
  `TomvdPeet\BreadcrumbBundle\Twig\BreadcrumbTrailExtension`.

## Attributes only

Doctrine-style annotations are removed in 2.x. Native PHP attributes are the
only controller metadata format loaded by the bundle.

Before:

```php
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

/**
 * @Breadcrumb("Books")
 */
final class BookController
{
}
```

After:

```php
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Breadcrumb('Books')]
final class BookController
{
}
```

If a controller mixed old annotations and PHP attributes, remove the annotations.
The `MixedAnnotationWithAttributeBreadcrumbsException` class was removed because
there is no annotation loader anymore.

### Breadcrumb title is required

`Breadcrumb` now requires a string title. Passing `null` or using an empty
`#[Breadcrumb]` attribute to reset the trail is no longer supported.

Before:

```php
#[Breadcrumb]
```

After:

```php
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;

#[ResetBreadcrumbTrail]
```

Template-only breadcrumb attributes are also no longer valid because the title
argument is required. Put the template on a real breadcrumb or configure the
template globally.

```php
#[Breadcrumb('Books', template: '@App/breadcrumbs.html.twig')]
```

### Custom attribute subclasses

The new attribute loader accepts attributes that extend
`TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb` or
`TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail`. If you previously
created custom wrapper attributes, update their parent classes to the new
namespace.

## Configuration

The configuration root changed from `apy_breadcrumb_trail` to
`tomvd_peet_breadcrumb`.

Before:

```yaml
apy_breadcrumb_trail:
    template: '@APYBreadcrumbTrail/breadcrumbtrail.html.twig'
```

After:

```yaml
tomvd_peet_breadcrumb:
    template: '@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig'
```

The default template is now
`@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig`.

## Services

The old public aliases were removed:

- `apy_breadcrumb_trail`
- `apy_breadcrumb_trail.annotation.listener`

Use autowiring with the new FQCNs instead:

```php
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;

public function index(Trail $trail): Response
{
}
```

If you reference services manually, update the IDs to the new class names. The
listener also has a new constructor that receives a breadcrumb loader and a
definition applier, so custom service definitions for
`BreadcrumbListener` must be updated or removed in favor of autowiring.

The old `src/Resources/config/services.xml` file was removed. If an application
or test imports bundle service definitions manually, import
`src/Resources/config/services.yaml` instead.

The 2.x branch also introduces
`TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbLoaderInterface`, aliased to the
attribute loader. Decorate or replace that service if you need custom breadcrumb
loading behavior.

## Twig

The Twig functions were renamed.

Before:

```twig
{{ apy_breadcrumb_trail_render() }}
{{ apy_breadcrumb_jsonld_render() }}
```

After:

```twig
{{ tomvd_peet_breadcrumb_trail_render() }}
{{ tomvd_peet_breadcrumb_jsonld_render() }}
```

If you expose the trail as a Twig global, update the service class:

```yaml
twig:
    globals:
        breadcrumb_trail: '@TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail'
```

Bundle template names changed from `@APYBreadcrumbTrail/...` to
`@TomvdPeetBreadcrumb/...`.

Symfony bundle override paths changed too:

```text
templates/bundles/TomvdPeetBreadcrumbBundle/breadcrumbtrail.html.twig
```

## PHP trail API

`Trail` and `Breadcrumb` now use typed signatures.

Most normal usage is unchanged:

```php
$trail
    ->add('Books', 'book_index')
    ->add('Current book')
;
```

Update code that passed loosely typed values:

- `Trail::add()` now accepts `Breadcrumb|string|null` as its first argument.
- Passing `null` to `Trail::add()` still resets the trail, but attributes should
  use `ResetBreadcrumbTrail`.
- `routeName` must be `?string`.
- `routeParameters` must be an array.
- `routeAbsolute` must be a bool.
- `position` must be an int.
- `attributes` must be an array.
- `Trail::setTemplate()` accepts `?string` and returns `self`.
- `Breadcrumb` constructor arguments are typed:
  `string $title`, `?string $url = null`, `mixed $attributes = []`.

Code that depended on the previous runtime `InvalidArgumentException` checks for
invalid title or position values will now usually fail earlier with PHP type
errors.

## Dynamic title and route parameter resolution

The placeholder syntax is still supported in breadcrumb titles and route
parameters:

```php
#[Breadcrumb(
    'Article {article.slug}',
    routeName: 'article_show',
    routeParameters: ['slug' => '{article.slug}', 'page' => '{page}']
)]
```

In 2.x, placeholders are resolved from request attributes. Older code used
`Request::get()`, which could also read query or request parameters. Move values
that breadcrumbs need into request attributes, or pass explicit route parameter
values yourself.

Numeric route-parameter entries still map a request attribute by name:

```php
#[Breadcrumb('Article', routeName: 'article_show', routeParameters: ['article'])]
```

Only string route-parameter values are scanned for placeholders. Non-string
values are passed through to the router unchanged.

## Automatic route name detection

Method-level `Breadcrumb` attributes can now infer the route name from a nearby
named Symfony `Route` attribute. When migrating from
`apy/breadcrumbtrail-bundle` or older versions/branches of this bundle, this
means many breadcrumbs no longer need to repeat the controller method route name.

Before:

```php
use Symfony\Component\Routing\Attribute\Route;
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Route('/books/{book}', name: 'book_show')]
#[Breadcrumb('Book {book.title}', routeName: 'book_show')]
public function show(Book $book): Response
{
}
```

After:

```php
use Symfony\Component\Routing\Attribute\Route;
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Route('/books/{book}', name: 'book_show')]
#[Breadcrumb('Book {book.title}')]
public function show(Book $book): Response
{
}
```

### What changed

- If a method-level breadcrumb has no `routeName`, the attribute loader checks
  the same method for Symfony `Route` attributes.
- The first named method route is used.
- A class-level Symfony route name is treated as a name prefix and is prepended
  to the method route name.
- If no named method route exists, the breadcrumb route name stays `null`.
- Explicit `routeName` values still win and do not trigger automatic detection.

### What to update when migrating

You may remove duplicated `routeName` arguments when the breadcrumb points to
the same route as the controller method:

```php
#[Route('/account', name: 'account_show')]
#[Breadcrumb('Account', routeName: 'account_show')]
```

can become:

```php
#[Route('/account', name: 'account_show')]
#[Breadcrumb('Account')]
```

Keep `routeName` explicit when the breadcrumb intentionally links to a different
route:

```php
#[Route('/books/{book}', name: 'book_show')]
#[Breadcrumb('Books', routeName: 'book_index')]
#[Breadcrumb('Book {book.title}')]
```

Also keep `routeName` explicit for class-level `Breadcrumb` attributes. Automatic
detection only applies to method-level breadcrumbs because the route name comes
from the controller method route.

## Documentation paths

Documentation moved from `src/Resources/doc/` to `docs/`.

Update internal links, package metadata, or tooling that points at the old
directory.

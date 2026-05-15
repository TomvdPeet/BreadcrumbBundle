# Migration Notes

This document is for applications and agents migrating to the current
`tomvdpeet/breadcrumb-bundle` from either:

- `apy/breadcrumbtrail-bundle`
- the `1.x`, `main`, or `master` branch of this bundle

It focuses on practical code changes and behavior differences that matter while
porting existing breadcrumb attributes.

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

# Breadcrumb Loading Roadmap

This document captures the planned direction for the breadcrumb loading overhaul.
It is intentionally implementation-focused so the work can be picked up across
separate sessions.

## Current Direction

Breadcrumb loading should be split into distinct responsibilities:

- loaders discover breadcrumb definitions from a source
- definitions describe unresolved breadcrumb intent
- an applier mutates the active trail from definitions
- the trail stores resolved breadcrumb items and remains responsible for its
  current public API until a later resolver extraction

The attribute loader is the first loader implementation. It reads controller
class and method attributes and returns definition objects instead of mutating
the trail directly.

## Planned Feature Order

1. Automatic route name detection (done)
2. Parent route support
3. Compiled production loader

This order matters. Parent route support depends on route names being present
more often, and the compiled production loader should compile the route-aware
model after parent route expansion exists.

## 1. Automatic Route Name Detection

Goal: improve DX by avoiding duplicated route names when a breadcrumb attribute
is placed next to a Symfony route attribute.

Example:

```php
#[Route('/books/{book}', name: 'book_show')]
#[Breadcrumb('Book {book.title}')]
public function show(Book $book): Response
{
}
```

The attribute loader should infer `book_show` as the breadcrumb route name when
`Breadcrumb::routeName` is not explicitly set.

Initial behavior:

- explicit `Breadcrumb::routeName` always wins
- read nearby Symfony `Route` attributes from the reflected method
- combine class-level route name prefixes with method-level route names
- if several named method routes exist, use the first named route initially
- if no explicit route name exists, leave the breadcrumb route name as `null`
- do not reproduce Symfony's generated unnamed-route naming algorithm yet

Recommended implementation:

- add a small route-name resolver used by `AttributeBreadcrumbLoader`
- inspect Symfony route attributes directly in the runtime attribute loader
- keep this logic out of `Trail`

Do not use the router route collection for this first runtime feature. The route
collection is authoritative, but it maps route names to routes, not controllers
to route names, and introduces ambiguity for multiple routes pointing to the
same controller. It is better suited to the later compiled loader.

## 2. Parent Route Support

Goal: allow a breadcrumb to inherit breadcrumb definitions from another route.

Example:

```php
#[Route('/books', name: 'book_index')]
#[Breadcrumb('Books')]
public function index(): Response
{
}

#[Route('/books/{book}', name: 'book_show')]
#[Breadcrumb('Book {book.title}', parentRoute: 'book_index')]
public function show(Book $book): Response
{
}
```

Rendering `book_show` should expand to:

```text
Books > Book Dune
```

Expected model changes:

- add `parentRoute` to the `Breadcrumb` attribute
- add `parentRoute` to `BreadcrumbDefinition`
- add the current route name to `BreadcrumbContext`
- introduce a route-keyed registry or expander that can resolve definitions for
  a route and walk parent routes
- add cycle detection for parent chains

Parent expansion should happen before definitions are applied to the trail.
Request-specific placeholders such as `{book.title}` must remain unresolved
until request time.

## 3. Compiled Production Loader

Goal: avoid request-time reflection and parent graph work in production.

The compiled loader should dump static breadcrumb metadata to the cache. The
runtime loader can then perform a cheap lookup by route or controller.

Likely compiled shape:

```php
return [
    'book_index' => [
        'parentRoute' => null,
        'definitions' => [
            [
                'title' => 'Books',
                'routeName' => 'book_index',
                'routeParameters' => [],
            ],
        ],
    ],
    'book_show' => [
        'parentRoute' => 'book_index',
        'definitions' => [
            [
                'title' => 'Book {book.title}',
                'routeName' => 'book_show',
                'routeParameters' => [],
            ],
        ],
    ],
];
```

The compiled loader should not pre-resolve dynamic placeholders. Values such as
`{book.title}` or `{book.id}` depend on the current request attributes and still
need request-time resolution.

Recommended approach:

- use the router route collection during cache warmup or container build
- iterate routes and normalize their `_controller` defaults
- reuse the attribute parsing logic to build definitions
- dump a PHP array or generated PHP class into the cache
- use the compiled loader by default in production
- optionally fall back to the runtime attribute loader when a key is missing

## Design Notes

Do not make breadcrumb loaders implement Symfony's generic config
`LoaderInterface` for now. Symfony's loader interface is resource/type based and
fits files, directories, classes, and config imports. Breadcrumb loading is
request/controller-context based, so the bundle-specific
`BreadcrumbLoaderInterface` is clearer.

Symfony routing does offer useful patterns to copy later:

- tagged loaders with priority
- a chain or resolver service
- route collection based compilation
- direct attribute parsing kept separate from runtime route matching

For this bundle, a future chain loader should probably combine definitions from
multiple loaders instead of selecting exactly one loader.

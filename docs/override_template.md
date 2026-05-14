# Override the template
You can override the default template in several ways:

 - You can put your new template in the app folder:

`templates/bundles/TomvdPeetBreadcrumbBundle/breadcrumbtrail.html.twig`

 - You can define the template in your config.yml file:

```yaml
tomvd_peet_breadcrumb:
    template: "@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig"
```

 - You can define another template in a breadcrumb attribute:

```php
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Breadcrumb("My breadcrumb", route: "my_route")]
#[Breadcrumb(template: "@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig")]
```

Or

```php
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Breadcrumb("My breadcrumb", route: "my_route", template: "@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig")]
```

 - You can define the template in PHP:

```php
/**
 * @see \TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail::setTemplate()
 */
$trail->setTemplate('@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig');
```

 - You can define the template when you render the breadcrumb trail in your twig file:

```twig
{{ tomvd_peet_breadcrumb_trail_render('@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig') }}
```

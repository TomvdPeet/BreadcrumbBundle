<?php

namespace TomvdPeet\BreadcrumbBundle\Exception;

final class AmbiguousBreadcrumbRouteNameException extends \RuntimeException
{
    /**
     * @param list<string> $routeNames
     */
    public static function forControllerMethod(string $controllerMethod, array $routeNames): self
    {
        return new self(sprintf(
            'Breadcrumb route name cannot be inferred for "%s" because it matches multiple named routes: "%s". Configure the breadcrumb routeName explicitly.',
            $controllerMethod,
            implode('", "', $routeNames)
        ));
    }
}

<?php

/*
 * This file is part of the APYBreadcrumbTrailBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\BreadcrumbTrailBundle\Annotation;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Breadcrumb
{
    /**
     * @param array|string|null $title title, or the legacy array that contains all annotation data. Passing `null` to reset the breadcrumb trail is deprecated and will throw an exception in `2.0`.
     * @param ?string $routeName
     * @param ?array<string,mixed> $routeParameters
     * @param bool $routeAbsolute
     * @param int $position
     * @param ?string $template
     * @param array $attributes
     */
    public function __construct(
        private array|string|null $title = null,
        private ?string           $routeName = null,
        private ?array            $routeParameters = [],
        private bool              $routeAbsolute = false,
        private int               $position = 0,
        private ?string           $template = null,
        private array             $attributes = []
    )
    {
    }

    /**
     * Sets the title of the breadcrumb.
     *
     * @param string $title The title of the breadcrumb
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the name of the route.
     *
     * @param string $routeName The name of the route
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Sets an array of parameters for the route.
     *
     * @param mixed $routeParameters An array of parameters for the route
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }

    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * Whether to generate an absolute URL.
     *
     * @param bool $routeAbsolute Whether to generate an absolute URL
     */
    public function setRouteAbsolute($routeAbsolute)
    {
        $this->routeAbsolute = $routeAbsolute;
    }

    public function getRouteAbsolute()
    {
        return $this->routeAbsolute;
    }

    /**
     * Sets the position of the breadcrumb.
     *
     * @param int $position Position of the breadcrumb (default = 0)
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets the template of the breadcrumb trail.
     *
     * @param string $template with path of the breadcrumb trail that should get rendered
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Sets the additional attributes for the breadcrumb.
     *
     * @param array $attributes additional attributes for the breadcrumb
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}

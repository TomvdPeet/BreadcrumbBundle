<?php

namespace TomvdPeet\BreadcrumbBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Breadcrumb
{
    public function __construct(
        private string $title,
        private ?string $routeName = null,
        private array $routeParameters = [],
        private bool $routeAbsolute = true,
        private int $position = 0,
        private ?string $template = null,
        private array $attributes = []
    )
    {
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setRouteName(?string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteParameters(array $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setRouteAbsolute(bool $routeAbsolute): void
    {
        $this->routeAbsolute = $routeAbsolute;
    }

    public function getRouteAbsolute(): bool
    {
        return $this->routeAbsolute;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}

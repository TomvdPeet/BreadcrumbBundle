<?php

namespace TomvdPeet\BreadcrumbBundle\Twig;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BreadcrumbTrailExtension extends AbstractExtension
{
    public function __construct(
        private Trail $trail,
        private Environment $templating
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tomvd_peet_breadcrumb_trail_render', [$this, 'renderBreadcrumbTrail'], ['is_safe' => ['html']]),
            new TwigFunction('tomvd_peet_breadcrumb_jsonld_render', [$this, 'renderBreadcrumbJsonld'], ['is_safe' => ['html']]),
        ];
    }

    public function renderBreadcrumbTrail(?string $template = null): string
    {
        return $this->templating->render(
            null === $template ? $this->trail->getTemplate() : $template,
            ['breadcrumbs' => $this->trail]
        );
    }

    public function renderBreadcrumbJsonld(): string
    {
        return $this->templating->render(
            '@TomvdPeetBreadcrumb/json-ld.html.twig',
            ['breadcrumbs' => $this->trail]
        );
    }

    public function getName(): string
    {
        return 'breadcrumbtrail';
    }
}

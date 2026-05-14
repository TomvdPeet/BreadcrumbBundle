<?php

/*
 * This file is part of the APYBreadcrumbTrailBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\BreadcrumbTrailBundle\Twig;

use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;
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
            new TwigFunction('apy_breadcrumb_trail_render', [$this, 'renderBreadcrumbTrail'], ['is_safe' => ['html']]),
            new TwigFunction('apy_breadcrumb_jsonld_render', [$this, 'renderBreadcrumbJsonld'], ['is_safe' => ['html']]),
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
            "@APYBreadcrumbTrail/json-ld.html.twig",
            ['breadcrumbs' => $this->trail]
        );
    }

    public function getName(): string
    {
        return 'breadcrumbtrail';
    }
}

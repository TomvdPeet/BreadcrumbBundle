<?php

namespace APY\BreadcrumbTrailBundle\Twig;

use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @coversDefaultClass \APY\BreadcrumbTrailBundle\Twig\BreadcrumbTrailExtension
 */
class BreadcrumbTrailExtensionTest extends TestCase
{
    public function testTwigFunctionGetsRegistered()
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $extension = new BreadcrumbTrailExtension($trail, $this->createStub(Environment::class));

        $function = current($extension->getFunctions());

        self::assertEquals('apy_breadcrumb_trail_render', $function->getName());
    }
}

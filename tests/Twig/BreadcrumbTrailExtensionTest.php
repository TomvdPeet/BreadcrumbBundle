<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Twig;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\Twig\BreadcrumbTrailExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @coversDefaultClass \TomvdPeet\BreadcrumbBundle\Twig\BreadcrumbTrailExtension
 */
class BreadcrumbTrailExtensionTest extends TestCase
{
    public function testTwigFunctionGetsRegistered(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $extension = new BreadcrumbTrailExtension($trail, $this->createStub(Environment::class));

        $functions = $extension->getFunctions();

        self::assertCount(2, $functions);
        self::assertEquals('tomvd_peet_breadcrumb_trail_render', $functions[0]->getName());
        self::assertEquals('tomvd_peet_breadcrumb_jsonld_render', $functions[1]->getName());
    }
}

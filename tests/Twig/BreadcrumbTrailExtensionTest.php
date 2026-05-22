<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Twig;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\Twig\BreadcrumbTrailExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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

    public function testRenderBreadcrumbTrailUsesTrailTemplateWhenNoTemplateIsPassed(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $trail->setTemplate('@App/breadcrumbs/from-trail.html.twig');

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with('@App/breadcrumbs/from-trail.html.twig', ['breadcrumbs' => $trail])
            ->willReturn('<nav>trail</nav>');

        $extension = new BreadcrumbTrailExtension($trail, $twig);

        self::assertSame('<nav>trail</nav>', $extension->renderBreadcrumbTrail());
    }

    public function testRenderBreadcrumbTrailAllowsTemplateOverrideAtRenderTime(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $trail->setTemplate('@App/breadcrumbs/from-trail.html.twig');

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with('@App/breadcrumbs/from-call.html.twig', ['breadcrumbs' => $trail])
            ->willReturn('<nav>override</nav>');

        $extension = new BreadcrumbTrailExtension($trail, $twig);

        self::assertSame('<nav>override</nav>', $extension->renderBreadcrumbTrail('@App/breadcrumbs/from-call.html.twig'));
    }

    public function testRenderBreadcrumbJsonLdProducesValidEscapedJson(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $trail->add('Home', attributes: ['class' => 'ignored']);
        $trail->add('Current "quoted" & </script>', attributes: ['class' => 'ignored']);

        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../src/Resources/views', 'TomvdPeetBreadcrumb');

        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension());

        $extension = new BreadcrumbTrailExtension($trail, $twig);
        $html = $extension->renderBreadcrumbJsonld();

        self::assertStringContainsString('<script type="application/ld+json">', $html);
        self::assertSame(1, substr_count($html, '</script>'));

        preg_match('#<script type="application/ld\+json">\s*(?P<json>.+?)\s*</script>#s', $html, $matches);

        self::assertNotEmpty($matches['json'] ?? null);

        $data = json_decode($matches['json'], true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('https://schema.org', $data['@context']);
        self::assertSame('BreadcrumbList', $data['@type']);
        self::assertSame('Current "quoted" & </script>', $data['itemListElement'][1]['item']['name']);
    }
}

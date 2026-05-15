<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\EventListener;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/ResetTrailAttribute.php';

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinitionApplier;
use TomvdPeet\BreadcrumbBundle\EventListener\BreadcrumbListener;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ResetTrailAttribute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BreadcrumbListenerTest extends TestCase
{
    public function testClassAndMethodBreadcrumbsAreAddedInDeclarationOrder(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $controller = new ControllerWithAttributes();
        $kernelEvent = $this->createControllerEvent($controller);
        $listener = $this->createListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertCount(3, $breadcrumbTrail);
        self::assertSame(
            ['first-breadcrumb', 'second-breadcrumb', 'third-breadcrumb'],
            array_column($this->breadcrumbSnapshot($breadcrumbTrail), 0)
        );
    }

    public function testResetTrailAttribute(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $controller = new ResetTrailAttribute();
        $kernelEvent = $this->createControllerEvent($controller);
        $listener = $this->createListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertCount(1, $breadcrumbTrail);
        self::assertSame(['first-breadcrumb-again'], array_column($this->breadcrumbSnapshot($breadcrumbTrail), 0));
    }

    public function testBreadcrumbPositionControlsOrderingAcrossClassAndMethodAttributes(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $kernelEvent = $this->createControllerEvent(new OrderedController());
        $listener = $this->createListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertSame(
            ['first', 'second', 'third', 'fourth'],
            array_column($this->breadcrumbSnapshot($breadcrumbTrail), 0)
        );
    }

    public function testTemplateOverrideUsesTheLastBreadcrumbTemplate(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $kernelEvent = $this->createControllerEvent(new TemplateController());
        $listener = $this->createListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertSame('@App/breadcrumbs/method.html.twig', $breadcrumbTrail->getTemplate());
    }

    public function testDynamicRouteParametersAreResolvedForAttributeBreadcrumbs(): void
    {
        $article = new Article('attribute-slug');
        $request = new Request([], [], [
            'article' => $article,
            'page' => 3,
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with(
                'article_show',
                ['slug' => 'attribute-slug', 'page' => 3],
                UrlGeneratorInterface::RELATIVE_PATH
            )
            ->willReturn('/articles/attribute-slug?page=3');

        $breadcrumbTrail = new Trail($router, $requestStack);
        $kernelEvent = $this->createControllerEvent(new DynamicRouteController(), $request);
        $listener = $this->createListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertSame(
            [['Article attribute-slug', '/articles/attribute-slug?page=3', []]],
            $this->breadcrumbSnapshot($breadcrumbTrail)
        );
    }

    private function createControllerEvent(object $controller, ?Request $request = null): ControllerEvent
    {
        $callable = \is_callable($controller) ? $controller : [$controller, 'indexAction'];

        return new ControllerEvent($this->createStub(HttpKernelInterface::class), $callable, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST);
    }

    private function createTrail(): Trail
    {
        return new Trail(
            $this->createStub(UrlGeneratorInterface::class),
            new RequestStack()
        );
    }

    private function createListener(Trail $trail): BreadcrumbListener
    {
        return new BreadcrumbListener($trail, new AttributeBreadcrumbLoader(), new BreadcrumbDefinitionApplier());
    }

    /**
     * @return list<array{0: string, 1: ?string, 2: mixed}>
     */
    private function breadcrumbSnapshot(Trail $trail): array
    {
        $snapshot = [];
        foreach ($trail as $breadcrumb) {
            self::assertInstanceOf(Breadcrumb::class, $breadcrumb);
            $snapshot[] = [$breadcrumb->title, $breadcrumb->url, $breadcrumb->attributes];
        }

        return $snapshot;
    }
}

#[BreadcrumbAttribute(title: 'first')]
#[BreadcrumbAttribute(title: 'fourth')]
final class OrderedController
{
    #[BreadcrumbAttribute(title: 'second', position: 2)]
    #[BreadcrumbAttribute(title: 'third', position: 3)]
    public function indexAction(): array
    {
        return [];
    }
}

#[BreadcrumbAttribute(title: 'class', template: '@App/breadcrumbs/class.html.twig')]
final class TemplateController
{
    #[BreadcrumbAttribute(title: 'method', template: '@App/breadcrumbs/method.html.twig')]
    public function indexAction(): array
    {
        return [];
    }
}

#[ResetBreadcrumbTrail]
final class DynamicRouteController
{
    #[BreadcrumbAttribute(
        title: 'Article {article.slug}',
        routeName: 'article_show',
        routeParameters: ['slug' => '{article.slug}', 'page' => '{page}'],
        routeAbsolute: false
    )]
    public function indexAction(): array
    {
        return [];
    }
}

final class Article
{
    public function __construct(private string $slug)
    {
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}

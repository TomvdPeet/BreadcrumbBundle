<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Route as RoutingRoute;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Compiler\CompiledBreadcrumbMetadataCompiler;
use TomvdPeet\BreadcrumbBundle\Exception\AmbiguousBreadcrumbRouteNameException;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Definition\ParentRouteDefinitionExpander;
use TomvdPeet\BreadcrumbBundle\Resolver\AttributeRouteNameResolver;
use TomvdPeet\BreadcrumbBundle\Resolver\RouteControllerResolver;

final class CompiledBreadcrumbMetadataCompilerTest extends TestCase
{
    public function testItCompilesExpandedBreadcrumbDefinitionsByRoute(): void
    {
        $compiler = $this->createCompiler([
            'compiled_parent' => CompiledParentController::class.'::indexAction',
            'compiled_child' => CompiledChildController::class.'::showAction',
        ]);

        $compiled = $compiler->compile();

        self::assertSame(['Home', 'Parent', 'Child'], array_column($compiled['compiled_child'], 'title'));
        self::assertSame('breadcrumb', $compiled['compiled_child'][0]['type']);
        self::assertArrayNotHasKey('parentRoute', $compiled['compiled_child'][2]);
    }

    public function testItKeepsDynamicPlaceholdersUnresolved(): void
    {
        $compiler = $this->createCompiler([
            'compiled_dynamic' => CompiledDynamicController::class.'::showAction',
        ]);

        $compiled = $compiler->compile();

        self::assertSame('Book {book.title}', $compiled['compiled_dynamic'][0]['title']);
        self::assertSame(['book' => '{book.id}'], $compiled['compiled_dynamic'][0]['routeParameters']);
    }

    public function testItThrowsDuringCompilationWhenParentRouteCannotBeFound(): void
    {
        $compiler = $this->createCompiler([
            'compiled_missing_parent_child' => CompiledMissingParentChildController::class.'::showAction',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parent breadcrumb route "compiled_missing_parent" could not be found.');

        $compiler->compile();
    }

    public function testItThrowsDuringCompilationWhenParentRouteChainContainsACycle(): void
    {
        $compiler = $this->createCompiler([
            'compiled_cycle_a' => CompiledCycleAController::class.'::indexAction',
            'compiled_cycle_b' => CompiledCycleBController::class.'::indexAction',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular breadcrumb parent route detected: compiled_cycle_a -> compiled_cycle_b -> compiled_cycle_a.');

        $compiler->compile();
    }

    public function testItSkipsRoutesWithoutReflectableControllers(): void
    {
        $compiler = $this->createCompiler([
            'compiled_parent' => CompiledParentController::class.'::indexAction',
            'service_route' => 'app.service_controller::index',
        ]);

        $compiled = $compiler->compile();

        self::assertArrayHasKey('compiled_parent', $compiled);
        self::assertArrayNotHasKey('service_route', $compiled);
    }

    public function testItUsesRouteCollectionNameWhenCompilingImplicitRouteName(): void
    {
        $compiler = $this->createCompiler([
            'compiled_collection_only' => CompiledRouteCollectionOnlyController::class.'::showAction',
        ]);

        $compiled = $compiler->compile();

        self::assertSame('compiled_collection_only', $compiled['compiled_collection_only'][0]['routeName']);
    }

    public function testItThrowsWhenCompilingImplicitBreadcrumbRouteNameForSeveralMatchingRoutes(): void
    {
        $compiler = $this->createCompiler([
            'compiled_collection_first' => CompiledRouteCollectionOnlyController::class.'::showAction',
            'compiled_collection_second' => CompiledRouteCollectionOnlyController::class.'::showAction',
        ]);

        $this->expectException(AmbiguousBreadcrumbRouteNameException::class);
        $this->expectExceptionMessage(sprintf(
            'Breadcrumb route name cannot be inferred for "%s::showAction" because it matches multiple named routes: "compiled_collection_first", "compiled_collection_second". Configure the breadcrumb routeName explicitly.',
            CompiledRouteCollectionOnlyController::class
        ));

        $compiler->compile();
    }

    /**
     * @param array<string,string> $controllersByRoute
     */
    private function createCompiler(array $controllersByRoute): CompiledBreadcrumbMetadataCompiler
    {
        $routeCollection = new RouteCollection();

        foreach ($controllersByRoute as $routeName => $controller) {
            $routeCollection->add($routeName, new RoutingRoute('/'.$routeName, [
                '_controller' => $controller,
            ]));
        }

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        return new CompiledBreadcrumbMetadataCompiler(
            $router,
            new AttributeBreadcrumbLoader(new AttributeRouteNameResolver($router, new RouteControllerResolver())),
            new ParentRouteDefinitionExpander(),
            new RouteControllerResolver()
        );
    }
}

#[Breadcrumb('Home')]
final class CompiledParentController
{
    #[Route('/parent', name: 'compiled_parent')]
    #[Breadcrumb('Parent')]
    public function indexAction(): array
    {
        return [];
    }
}

final class CompiledChildController
{
    #[Route('/child', name: 'compiled_child')]
    #[Breadcrumb('Child', parentRoute: 'compiled_parent')]
    public function showAction(): array
    {
        return [];
    }
}

final class CompiledDynamicController
{
    #[Route('/books/{book}', name: 'compiled_dynamic')]
    #[Breadcrumb('Book {book.title}', routeParameters: ['book' => '{book.id}'])]
    public function showAction(): array
    {
        return [];
    }
}

final class CompiledRouteCollectionOnlyController
{
    #[Breadcrumb('Collection only')]
    public function showAction(): array
    {
        return [];
    }
}

final class CompiledMissingParentChildController
{
    #[Route('/missing-parent-child', name: 'compiled_missing_parent_child')]
    #[Breadcrumb('Child', parentRoute: 'compiled_missing_parent')]
    public function showAction(): array
    {
        return [];
    }
}

final class CompiledCycleAController
{
    #[Route('/cycle-a', name: 'compiled_cycle_a')]
    #[Breadcrumb('A', parentRoute: 'compiled_cycle_b')]
    public function indexAction(): array
    {
        return [];
    }
}

final class CompiledCycleBController
{
    #[Route('/cycle-b', name: 'compiled_cycle_b')]
    #[Breadcrumb('B', parentRoute: 'compiled_cycle_a')]
    public function indexAction(): array
    {
        return [];
    }
}

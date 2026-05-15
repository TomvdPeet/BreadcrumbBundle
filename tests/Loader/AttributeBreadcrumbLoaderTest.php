<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Loader;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/InvokableControllerWithAttributes.php';

use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Resolver\AttributeRouteNameResolver;
use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\ParentRouteDefinitionExpander;
use TomvdPeet\BreadcrumbBundle\Loader\ParentRouteBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Resolver\ParentRouteControllerResolver;
use TomvdPeet\BreadcrumbBundle\Resolver\RouteControllerResolver;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\InvokableControllerWithAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Route as RoutingRoute;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class AttributeBreadcrumbLoaderTest extends TestCase
{
    public function testItLoadsClassAndMethodBreadcrumbAttributesInDeclarationOrder(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new ControllerWithAttributes(), 'indexAction']
        )));

        self::assertContainsOnlyInstancesOf(BreadcrumbDefinition::class, $definitions);
        self::assertSame(
            ['first-breadcrumb', 'second-breadcrumb', 'third-breadcrumb'],
            array_map(static fn (BreadcrumbDefinition $definition): string => $definition->title, $definitions)
        );
    }

    public function testItLoadsInvokableControllerAttributes(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            new InvokableControllerWithAttributes()
        )));

        self::assertContainsOnlyInstancesOf(BreadcrumbDefinition::class, $definitions);
        self::assertCount(3, $definitions);
    }

    public function testItConvertsResetAndTemplateAttributesToDefinitions(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new LoaderDefinitionController(), 'indexAction']
        )));

        self::assertInstanceOf(ResetTrailDefinition::class, $definitions[0]);
        self::assertInstanceOf(TemplateDefinition::class, $definitions[1]);
        self::assertSame('@App/breadcrumbs/class.html.twig', $definitions[1]->template);
        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[2]);
        self::assertSame('class', $definitions[2]->title);
        self::assertInstanceOf(TemplateDefinition::class, $definitions[3]);
        self::assertSame('@App/breadcrumbs/method.html.twig', $definitions[3]->template);
        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[4]);
        self::assertSame('method', $definitions[4]->title);
    }

    public function testItLoadsAttributesThatExtendSupportedBreadcrumbAttributes(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new CustomAttributeController(), 'indexAction']
        )));

        self::assertInstanceOf(ResetTrailDefinition::class, $definitions[0]);
        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[1]);
        self::assertSame('custom class', $definitions[1]->title);
        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[2]);
        self::assertSame('custom method', $definitions[2]->title);
    }

    public function testItInfersMethodBreadcrumbRouteNameFromNearbyRouteAttribute(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'showAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertSame('book_show', $definitions[0]->routeName);
    }

    public function testExplicitBreadcrumbRouteNameWinsOverInferredRouteName(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'explicitAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertSame('custom_route', $definitions[0]->routeName);
    }

    public function testItDoesNotResolveRouteNameWhenMethodBreadcrumbsAlreadyHaveExplicitRouteNames(): void
    {
        $resolver = new CountingAttributeRouteNameResolver();
        $loader = new AttributeBreadcrumbLoader($resolver);

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'explicitAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertSame('custom_route', $definitions[0]->routeName);
        self::assertSame(0, $resolver->resolveCalls);
    }

    public function testItResolvesRouteNameOnlyOnceForSeveralImplicitMethodBreadcrumbs(): void
    {
        $resolver = new CountingAttributeRouteNameResolver();
        $loader = new AttributeBreadcrumbLoader($resolver);

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'severalImplicitBreadcrumbsAction']
        )));

        self::assertContainsOnlyInstancesOf(BreadcrumbDefinition::class, $definitions);
        self::assertSame(['several_implicit', 'several_implicit'], array_map(
            static fn (BreadcrumbDefinition $definition): ?string => $definition->routeName,
            $definitions
        ));
        self::assertSame(1, $resolver->resolveCalls);
    }

    public function testItCombinesClassLevelRouteNamePrefixWithMethodRouteName(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new PrefixedAutomaticRouteNameController(), 'showAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertSame('admin_book_show', $definitions[0]->routeName);
    }

    public function testItUsesFirstNamedMethodRouteWhenSeveralExist(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'multipleRoutesAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertSame('first_named_route', $definitions[0]->routeName);
    }

    public function testItLeavesBreadcrumbRouteNameNullWhenNoNamedRouteExists(): void
    {
        $loader = new AttributeBreadcrumbLoader();

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request(),
            [new AutomaticRouteNameController(), 'unnamedAction']
        )));

        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[0]);
        self::assertNull($definitions[0]->routeName);
    }

    public function testItExpandsParentRouteBreadcrumbsBeforeCurrentRouteBreadcrumbs(): void
    {
        $loader = $this->createParentRouteLoader([
            'parent_index' => ParentRouteIndexController::class.'::indexAction',
        ]);

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'child_show']),
            [new ParentRouteChildController(), 'showAction']
        )));

        self::assertSame(
            ['Parent', 'Child'],
            array_map(static fn (BreadcrumbDefinition $definition): string => $definition->title, $definitions)
        );
    }

    public function testItExpandsNestedParentRouteBreadcrumbs(): void
    {
        $loader = $this->createParentRouteLoader([
            'grandparent_index' => GrandparentRouteController::class.'::indexAction',
            'parent_index' => NestedParentRouteController::class.'::indexAction',
        ]);

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'child_show']),
            [new NestedChildRouteController(), 'showAction']
        )));

        self::assertSame(
            ['Grandparent', 'Parent', 'Child'],
            array_map(static fn (BreadcrumbDefinition $definition): string => $definition->title, $definitions)
        );
    }

    public function testItThrowsWhenParentRouteCannotBeFound(): void
    {
        $loader = $this->createParentRouteLoader([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parent breadcrumb route "missing_parent" could not be found.');

        iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'child_show']),
            [new MissingParentRouteController(), 'showAction']
        )));
    }

    public function testItThrowsWhenParentRouteChainContainsACycle(): void
    {
        $loader = $this->createParentRouteLoader([
            'route_a' => CycleRouteAController::class.'::indexAction',
            'route_b' => CycleRouteBController::class.'::indexAction',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular breadcrumb parent route detected: route_a -> route_b -> route_a.');

        iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'route_a']),
            [new CycleRouteAController(), 'indexAction']
        )));
    }

    public function testItLoadsClassBreadcrumbsOnlyForTheTerminalParentRoute(): void
    {
        $loader = $this->createParentRouteLoader([
            'class_base_index' => ClassLevelBaseParentController::class.'::indexAction',
        ]);

        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'class_base_child']),
            [new ClassLevelBaseChildController(), 'showAction']
        )));

        self::assertSame(
            ['Home', 'Base', 'Child'],
            array_map(static fn (BreadcrumbDefinition $definition): string => $definition->title, $definitions)
        );
    }

    public function testItThrowsWhenBreadcrumbsAreDefinedBeforeParentRouteBoundary(): void
    {
        $loader = $this->createParentRouteLoader([
            'parent_index' => ParentRouteIndexController::class.'::indexAction',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Breadcrumb route "replaced_before_parent" defines breadcrumb attributes before its parentRoute boundary.');

        iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'replaced_before_parent']),
            [new ReplacedBeforeParentRouteController(), 'indexAction']
        )));
    }

    public function testItThrowsWhenMethodDefinesSeveralParentRouteBoundaries(): void
    {
        $loader = $this->createParentRouteLoader([
            'parent_index' => ParentRouteIndexController::class.'::indexAction',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Breadcrumb route "several_parent_boundaries" defines multiple parentRoute boundaries.');

        iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'several_parent_boundaries']),
            [new SeveralParentRouteBoundariesController(), 'indexAction']
        )));
    }

    /**
     * @param array<string,string> $controllersByRoute
     */
    private function createParentRouteLoader(array $controllersByRoute): ParentRouteBreadcrumbLoader
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

        return new ParentRouteBreadcrumbLoader(
            new AttributeBreadcrumbLoader(),
            new ParentRouteControllerResolver($router, new RouteControllerResolver()),
            new ParentRouteDefinitionExpander()
        );
    }
}

#[\TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail]
#[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'class', template: '@App/breadcrumbs/class.html.twig')]
final class LoaderDefinitionController
{
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'method', template: '@App/breadcrumbs/method.html.twig')]
    public function indexAction(): array
    {
        return [];
    }
}

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class CustomBreadcrumb extends \TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb
{
}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class CustomResetBreadcrumbTrail extends \TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail
{
}

#[CustomResetBreadcrumbTrail]
#[CustomBreadcrumb(title: 'custom class')]
final class CustomAttributeController
{
    #[CustomBreadcrumb(title: 'custom method')]
    public function indexAction(): array
    {
        return [];
    }
}

final class AutomaticRouteNameController
{
    #[Route('/books/{book}', name: 'book_show')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Book')]
    public function showAction(): array
    {
        return [];
    }

    #[Route('/explicit/{book}', name: 'book_explicit')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Book', routeName: 'custom_route')]
    public function explicitAction(): array
    {
        return [];
    }

    #[Route('/first')]
    #[Route('/second', name: 'first_named_route')]
    #[Route('/third', name: 'second_named_route')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Multiple routes')]
    public function multipleRoutesAction(): array
    {
        return [];
    }

    #[Route('/unnamed')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Unnamed')]
    public function unnamedAction(): array
    {
        return [];
    }

    #[Route('/several-implicit', name: 'several_implicit')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'First implicit')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Second implicit')]
    public function severalImplicitBreadcrumbsAction(): array
    {
        return [];
    }
}

final class ParentRouteIndexController
{
    #[Route('/parent', name: 'parent_index')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Parent')]
    public function indexAction(): array
    {
        return [];
    }
}

final class ParentRouteChildController
{
    #[Route('/child', name: 'child_show')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child', parentRoute: 'parent_index')]
    public function showAction(): array
    {
        return [];
    }
}

final class GrandparentRouteController
{
    #[Route('/grandparent', name: 'grandparent_index')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Grandparent')]
    public function indexAction(): array
    {
        return [];
    }
}

final class NestedParentRouteController
{
    #[Route('/parent', name: 'parent_index')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Parent', parentRoute: 'grandparent_index')]
    public function indexAction(): array
    {
        return [];
    }
}

final class NestedChildRouteController
{
    #[Route('/child', name: 'child_show')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child', parentRoute: 'parent_index')]
    public function showAction(): array
    {
        return [];
    }
}

final class MissingParentRouteController
{
    #[Route('/child', name: 'child_show')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child', parentRoute: 'missing_parent')]
    public function showAction(): array
    {
        return [];
    }
}

final class CycleRouteAController
{
    #[Route('/a', name: 'route_a')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'A', parentRoute: 'route_b')]
    public function indexAction(): array
    {
        return [];
    }
}

final class CycleRouteBController
{
    #[Route('/b', name: 'route_b')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'B', parentRoute: 'route_a')]
    public function indexAction(): array
    {
        return [];
    }
}

#[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Home')]
#[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Base')]
final class ClassLevelBaseParentController
{
    #[Route('/class-base', name: 'class_base_index')]
    public function indexAction(): array
    {
        return [];
    }
}

final class ClassLevelBaseChildController
{
    #[Route('/class-base/child', name: 'class_base_child')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child', parentRoute: 'class_base_index')]
    public function showAction(): array
    {
        return [];
    }
}

final class ReplacedBeforeParentRouteController
{
    #[Route('/replaced-before-parent', name: 'replaced_before_parent')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Old local base')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child one', parentRoute: 'parent_index')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child two')]
    public function indexAction(): array
    {
        return [];
    }
}

final class SeveralParentRouteBoundariesController
{
    #[Route('/several-parent-boundaries', name: 'several_parent_boundaries')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child one', parentRoute: 'parent_index')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Child two', parentRoute: 'second_parent')]
    public function indexAction(): array
    {
        return [];
    }
}

#[Route('/admin', name: 'admin_')]
final class PrefixedAutomaticRouteNameController
{
    #[Route('/books/{book}', name: 'book_show')]
    #[\TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb(title: 'Book')]
    public function showAction(): array
    {
        return [];
    }
}

final class CountingAttributeRouteNameResolver extends AttributeRouteNameResolver
{
    public int $resolveCalls = 0;

    public function resolve(\ReflectionClass $class, \ReflectionMethod $method): ?string
    {
        ++$this->resolveCalls;

        return parent::resolve($class, $method);
    }
}

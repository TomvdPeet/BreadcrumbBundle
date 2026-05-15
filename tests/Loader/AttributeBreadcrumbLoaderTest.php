<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Loader;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/InvokableControllerWithAttributes.php';

use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeRouteNameResolver;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\InvokableControllerWithAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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

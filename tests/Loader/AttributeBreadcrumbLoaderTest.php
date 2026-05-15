<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Loader;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/InvokableControllerWithAttributes.php';

use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\InvokableControllerWithAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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

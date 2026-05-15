<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbLoaderInterface;
use TomvdPeet\BreadcrumbBundle\Loader\CompiledBreadcrumbLoader;

final class CompiledBreadcrumbLoaderTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir().'/compiled_breadcrumb_loader_test_'.bin2hex(random_bytes(4)).'.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testItLoadsDefinitionsFromCompiledCacheFile(): void
    {
        file_put_contents($this->cacheFile, "<?php\n\nreturn ".var_export([
            'compiled_route' => [
                ['type' => 'reset'],
                ['type' => 'template', 'template' => '@App/breadcrumb.html.twig'],
                [
                    'type' => 'breadcrumb',
                    'title' => 'Compiled',
                    'routeName' => 'compiled_route',
                    'routeParameters' => ['slug' => '{slug}'],
                    'routeAbsolute' => false,
                    'position' => 2,
                    'attributes' => ['class' => 'item'],
                ],
            ],
        ], true).";\n");

        $loader = new CompiledBreadcrumbLoader($this->cacheFile);
        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'compiled_route']),
            [new \stdClass(), '__invoke']
        )));

        self::assertInstanceOf(ResetTrailDefinition::class, $definitions[0]);
        self::assertInstanceOf(TemplateDefinition::class, $definitions[1]);
        self::assertSame('@App/breadcrumb.html.twig', $definitions[1]->template);
        self::assertInstanceOf(BreadcrumbDefinition::class, $definitions[2]);
        self::assertSame('Compiled', $definitions[2]->title);
        self::assertSame(['slug' => '{slug}'], $definitions[2]->routeParameters);
        self::assertFalse($definitions[2]->routeAbsolute);
        self::assertSame(2, $definitions[2]->position);
        self::assertSame(['class' => 'item'], $definitions[2]->attributes);
    }

    public function testItFallsBackWhenRouteIsMissingFromCompiledCache(): void
    {
        file_put_contents($this->cacheFile, "<?php\n\nreturn [];\n");

        $fallbackLoader = new class implements BreadcrumbLoaderInterface {
            public function load(BreadcrumbContext $context): iterable
            {
                yield new BreadcrumbDefinition('Fallback');
            }
        };

        $loader = new CompiledBreadcrumbLoader($this->cacheFile, $fallbackLoader);
        $definitions = iterator_to_array($loader->load(new BreadcrumbContext(
            new Request([], [], ['_route' => 'missing_route']),
            [new \stdClass(), '__invoke']
        )));

        self::assertCount(1, $definitions);
        self::assertSame('Fallback', $definitions[0]->title);
    }
}

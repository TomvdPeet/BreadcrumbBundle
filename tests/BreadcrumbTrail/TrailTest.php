<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\BreadcrumbTrail;

use PHPUnit\Framework\TestCase;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrailTest extends TestCase
{
    public function testItCanAddBreadcrumbObjectsAndGeneratedRouteBreadcrumbs(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('article_show', ['slug' => 'existing-behavior'], UrlGeneratorInterface::RELATIVE_PATH)
            ->willReturn('/articles/existing-behavior');

        $trail = new Trail($router, new RequestStack());
        $trail->add(new Breadcrumb('Home', '/'));
        $trail->add('Article', 'article_show', ['slug' => 'existing-behavior'], false, attributes: ['data-current' => 'true']);

        self::assertSame(
            [
                ['Home', '/', []],
                ['Article', '/articles/existing-behavior', ['data-current' => 'true']],
            ],
            $this->breadcrumbSnapshot($trail)
        );
    }

    public function testResetClearsTheTrail(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $trail->add('Home');
        $trail->add('Section');

        $result = $trail->reset();

        self::assertSame($trail, $result);
        self::assertCount(0, $trail);
    }

    public function testNullBreadcrumbResetsTheTrail(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());
        $trail->add('Home');
        $trail->add(null);

        self::assertCount(0, $trail);
    }

    public function testBreadcrumbsCanBeInsertedAtSpecificPositions(): void
    {
        $trail = new Trail($this->createStub(UrlGeneratorInterface::class), new RequestStack());

        $trail->add('First');
        $trail->add('Third');
        $trail->add('Second', position: 2);
        $trail->add('Before third', position: -1);

        self::assertSame(
            ['First', 'Second', 'Before third', 'Third'],
            array_column($this->breadcrumbSnapshot($trail), 0)
        );
    }

    public function testDynamicRouteParametersAreResolvedFromTheCurrentRequest(): void
    {
        $user = new User('sample-name');
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with(
                'article_show',
                [
                    'slug' => 'sample-name',
                    'page' => 4,
                    'user' => $user,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://example.test/articles/sample-name?page=4');

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [
            'user' => $user,
            'page' => 4,
        ]));

        $trail = new Trail($router, $requestStack);
        $trail->add(
            'Article {user.name}',
            'article_show',
            ['slug' => '{user.name}', 'page' => '{page}', 'user']
        );

        self::assertSame(
            [['Article sample-name', 'https://example.test/articles/sample-name?page=4', []]],
            $this->breadcrumbSnapshot($trail)
        );
    }

    public function testRenderSimpleValueObjectValueInBreadcrumbTitle(): void
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        $requestStack = new RequestStack();

        $expected = 'sample-name';
        $requestStack->push(new Request([], [], [
            'user' => new User($expected),
        ]));

        $trail = new Trail($router, $requestStack);
        $trail->add('{user.name}');

        $iterator = $trail->getIterator();
        self::assertCount(1, $iterator);

        $breadcrumb = $iterator->current();
        self::assertInstanceOf(Breadcrumb::class, $breadcrumb);
        self::assertEquals($expected, $breadcrumb->title);
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

final class User
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

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

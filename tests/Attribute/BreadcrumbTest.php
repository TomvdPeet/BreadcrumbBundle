<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

class BreadcrumbTest extends TestCase
{
    public function testConstructWithSimpleTitle(): void
    {
        $expected = 'title-of-the-breadcrumb';
        $breadcrumb = new Breadcrumb($expected);

        self::assertEquals($expected, $breadcrumb->title);
    }

    public function testConstructWithParentRoute(): void
    {
        $breadcrumb = new Breadcrumb('Book', parentRoute: 'book_index');

        self::assertSame('book_index', $breadcrumb->parentRoute);
    }
}

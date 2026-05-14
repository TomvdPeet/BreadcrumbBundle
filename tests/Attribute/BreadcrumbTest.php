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

        self::assertEquals($expected, $breadcrumb->getTitle());
    }
}

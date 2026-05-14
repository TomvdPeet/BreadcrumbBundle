<?php

namespace APY\BreadcrumbTrailBundle\Annotation;

use PHPUnit\Framework\TestCase;

class BreadcrumbTest extends TestCase
{
    public function testConstructWithSimpleTitle(): void
    {
        $expected = 'title-of-the-breadcrumb';
        $breadcrumb = new Breadcrumb($expected);

        self::assertEquals($expected, $breadcrumb->getTitle());
    }
}

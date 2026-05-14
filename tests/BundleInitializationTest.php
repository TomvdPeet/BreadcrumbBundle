<?php

namespace TomvdPeet\BreadcrumbBundle\Tests;

use PHPUnit\Framework\TestCase;
use TomvdPeet\BreadcrumbBundle\TomvdPeetBreadcrumbBundle;

class BundleInitializationTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        self::assertInstanceOf(TomvdPeetBreadcrumbBundle::class, new TomvdPeetBreadcrumbBundle());
    }
}

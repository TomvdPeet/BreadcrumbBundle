<?php

namespace APY\BreadcrumbTrailBundle;

use PHPUnit\Framework\TestCase;

class BundleInitializationTest extends TestCase
{
    public function testBundleCanBeInstantiated()
    {
        self::assertInstanceOf(APYBreadcrumbTrailBundle::class, new APYBreadcrumbTrailBundle());
    }
}

<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;

interface BreadcrumbLoaderInterface
{
    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function load(BreadcrumbContext $context): iterable;
}

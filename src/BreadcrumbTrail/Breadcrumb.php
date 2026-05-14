<?php

/*
 * This file is part of the APYBreadcrumbTrailBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\BreadcrumbTrailBundle\BreadcrumbTrail;

class Breadcrumb
{
    public function __construct(
        public string $title,
        public ?string $url = null,
        public mixed $attributes = []
    )
    {
    }
}

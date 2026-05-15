<?php

namespace TomvdPeet\BreadcrumbBundle\EventListener;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinitionApplier;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbLoaderInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BreadcrumbListener
{
    public function __construct(
        protected Trail $breadcrumbTrail,
        private BreadcrumbLoaderInterface $breadcrumbLoader,
        private BreadcrumbDefinitionApplier $definitionApplier
    )
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }

        $this->breadcrumbTrail->reset();

        foreach ($this->breadcrumbLoader->load(new BreadcrumbContext($event->getRequest(), $event->getController())) as $definition) {
            $this->definitionApplier->apply($definition, $this->breadcrumbTrail);
        }
    }
}

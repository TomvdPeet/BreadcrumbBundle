<?php

/*
 * This file is part of the BreadcrumbBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TomvdPeet\BreadcrumbBundle\EventListener;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BreadcrumbListener
{
    private const SUPPORTED_ATTRIBUTES = [
        Breadcrumb::class,
        ResetBreadcrumbTrail::class,
    ];


    public function __construct(
        protected Trail $breadcrumbTrail
    )
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }

        $controller = $event->getController();

        $reflectableClass = \is_array($controller) ? $controller[0] : $controller;
        $reflectableMethod = \is_array($controller) ? $controller[1] : '__invoke';

        $class = new \ReflectionClass($reflectableClass);

        $this->breadcrumbTrail->reset();

        $classBreadcrumbs = $this->getAttributes($class);
        $this->addBreadcrumbsToTrail($classBreadcrumbs);

        $method = $class->getMethod($reflectableMethod);

        $methodBreadcrumbs = $this->getAttributes($method);
        $this->addBreadcrumbsToTrail($methodBreadcrumbs);
    }

    /**
     * @param list<Breadcrumb|ResetBreadcrumbTrail> $attributes
     */
    private function addBreadcrumbsToTrail(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof ResetBreadcrumbTrail) {
                $this->breadcrumbTrail->reset();

                continue;
            }

            $template = $attribute->getTemplate();
            $title = $attribute->getTitle();

            if (null != $template) {
                $this->breadcrumbTrail->setTemplate($template);
            }

            $this->breadcrumbTrail->add(
                $title,
                $attribute->getRouteName(),
                $attribute->getRouteParameters(),
                $attribute->getRouteAbsolute(),
                $attribute->getPosition(),
                $attribute->getAttributes()
            );
        }
    }

    /**
     * @return list<Breadcrumb|ResetBreadcrumbTrail>
     */
    private function getAttributes(\ReflectionClass|\ReflectionMethod $reflected): array
    {
        $attributes = [];
        foreach ($reflected->getAttributes() as $reflectionAttribute) {
            if (false === \in_array($reflectionAttribute->getName(), self::SUPPORTED_ATTRIBUTES)) {
                continue;
            }

            $attributes[] = $reflectionAttribute->newInstance();
        }

        return $attributes;
    }
}

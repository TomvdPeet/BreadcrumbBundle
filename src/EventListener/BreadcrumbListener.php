<?php

/*
 * This file is part of the APYBreadcrumbTrailBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\BreadcrumbTrailBundle\EventListener;

use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use APY\BreadcrumbTrailBundle\Annotation\ResetBreadcrumbTrail;
use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BreadcrumbListener
{
    const SUPPORTED_ATTRIBUTES = [
        Breadcrumb::class,
        ResetBreadcrumbTrail::class,
    ];


    public function __construct(
        protected Trail $breadcrumbTrail
    )
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }

        $controller = $event->getController();

        $reflectableClass = \is_array($controller) ? $controller[0] : $controller;
        $reflectableMethod = \is_array($controller) ? $controller[1] : '__invoke';

        // Annotations from class
        $class = new \ReflectionClass($reflectableClass);

        $this->breadcrumbTrail->reset();

        //TODO: only add if method has attribute

        //Breadcrumbs from class
        $classBreadcrumbs = $this->getAttributes($class);
        $this->addBreadcrumbsToTrail($classBreadcrumbs);

        // Breadcrumbs from method
        $method = $class->getMethod($reflectableMethod);

        $methodBreadcrumbs = $this->getAttributes($method);
        $this->addBreadcrumbsToTrail($methodBreadcrumbs);
    }

    /**
     * @param array $annotations Array of Breadcrumb annotations
     */
    private function addBreadcrumbsToTrail(array $annotations)
    {
        // requirements (@Breadcrumb)
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ResetBreadcrumbTrail) {
                $this->breadcrumbTrail->reset();

                continue;
            }

            if ($annotation instanceof Breadcrumb) {
                $template = $annotation->getTemplate();
                $title = $annotation->getTitle();

                if (null === $title) {
                    trigger_deprecation('apy/breadcrumb-bundle', '1.8', 'Resetting the breadcrumb trail by passing a Breadcrumb without parameters, and will throw an exception in v2.0. Use #[ResetBreadcrumbTrail] attribute instead.');
                }

                if (null != $template) {
                    $this->breadcrumbTrail->setTemplate($template);
                    if (null === $title) {
                        continue;
                    }
                }

                $this->breadcrumbTrail->add(
                    $title,
                    $annotation->getRouteName(),
                    $annotation->getRouteParameters(),
                    $annotation->getRouteAbsolute(),
                    $annotation->getPosition(),
                    $annotation->getAttributes()
                );
            }
        }
    }

    private function supportsLoadingAttributes(): bool
    {
        return \PHP_VERSION_ID >= 80000;
    }

    /**
     * @param \ReflectionClass|\ReflectionMethod $reflected
     *
     * @return array<Breadcrumb>
     */
    private function getAttributes($reflected): array
    {
        if (false === $this->supportsLoadingAttributes()) {
            throw new \RuntimeException('Detected an attempt on getting attributes while your version of PHP does not support this.');
        }

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

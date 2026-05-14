<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\EventListener;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\EventListener\BreadcrumbListener;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ResetTrailAttribute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BreadcrumbListenerTest extends TestCase
{
    public function testAttributes(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $controller = new ControllerWithAttributes();
        $kernelEvent = $this->createControllerEvent($controller);
        $listener = new BreadcrumbListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertCount(3, $breadcrumbTrail);
    }

    public function testResetTrailAttribute(): void
    {
        $breadcrumbTrail = $this->createTrail();

        $controller = new ResetTrailAttribute();
        $kernelEvent = $this->createControllerEvent($controller);
        $listener = new BreadcrumbListener($breadcrumbTrail);
        $listener->onKernelController($kernelEvent);

        self::assertCount(1, $breadcrumbTrail);
    }

    private function createControllerEvent(object $controller): ControllerEvent
    {
        $callable = \is_callable($controller) ? $controller : [$controller, 'indexAction'];

        return new ControllerEvent($this->createStub(HttpKernelInterface::class), $callable, new Request(), HttpKernelInterface::MAIN_REQUEST);
    }

    private function createTrail(): Trail
    {
        return new Trail(
            $this->createStub(UrlGeneratorInterface::class),
            new RequestStack()
        );
    }
}

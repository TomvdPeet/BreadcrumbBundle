<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\EventListener;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/InvokableControllerWithAttributes.php';

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinitionApplier;
use TomvdPeet\BreadcrumbBundle\EventListener\BreadcrumbListener;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\ControllerWithAttributes;
use TomvdPeet\BreadcrumbBundle\Tests\Fixtures\InvokableControllerWithAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BreadcrumbListenerControllerResolutionTest extends TestCase
{
    public function testItSupportsArrayControllerCallables(): void
    {
        $trail = $this->createTrail();
        $listener = $this->createListener($trail);
        $controller = new ControllerWithAttributes();
        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            [$controller, 'indexAction'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelController($event);

        self::assertCount(3, $trail);
    }

    public function testItSupportsInvokableControllerObjects(): void
    {
        $trail = $this->createTrail();
        $listener = $this->createListener($trail);
        $controller = new InvokableControllerWithAttributes();
        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            $controller,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelController($event);

        self::assertCount(3, $trail);
    }

    private function createTrail(): Trail
    {
        return new Trail(
            $this->createStub(UrlGeneratorInterface::class),
            new RequestStack()
        );
    }

    private function createListener(Trail $trail): BreadcrumbListener
    {
        return new BreadcrumbListener($trail, new AttributeBreadcrumbLoader(), new BreadcrumbDefinitionApplier());
    }
}

<?php

namespace APY\BreadcrumbTrailBundle\EventListener;

require_once __DIR__.'/../Fixtures/ControllerWithAttributes.php';
require_once __DIR__.'/../Fixtures/InvokableControllerWithAttributes.php';

use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;
use APY\BreadcrumbTrailBundle\Fixtures\ControllerWithAttributes;
use APY\BreadcrumbTrailBundle\Fixtures\InvokableControllerWithAttributes;
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
        $listener = new BreadcrumbListener($trail);
        $controller = new ControllerWithAttributes();
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
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
        $listener = new BreadcrumbListener($trail);
        $controller = new InvokableControllerWithAttributes();
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(UrlGeneratorInterface::class),
            new RequestStack()
        );
    }
}

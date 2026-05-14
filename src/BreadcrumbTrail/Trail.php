<?php

/*
 * This file is part of the BreadcrumbBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TomvdPeet\BreadcrumbBundle\BreadcrumbTrail;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Trail implements \IteratorAggregate, \Countable
{
    /**
     * @var \SplObjectStorage<Breadcrumb>
     */
    private \SplObjectStorage $breadcrumbs;

    private ?string $template = null;

    public function __construct(
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack
    )
    {
        $this->breadcrumbs = new \SplObjectStorage();
    }

    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param array<string|int,mixed> $routeParameters
     * @param array<string,mixed>     $attributes
     *
     * @throws \RuntimeException
     */
    public function add(Breadcrumb|string|null $breadcrumbOrTitle, ?string $routeName = null, array $routeParameters = [], bool $routeAbsolute = true, int $position = 0, array $attributes = []): self
    {
        if (null === $breadcrumbOrTitle) {
            return $this->reset();
        }

        if ($breadcrumbOrTitle instanceof Breadcrumb) {
            $breadcrumb = $breadcrumbOrTitle;
        } else {
            $request = $this->requestStack->getCurrentRequest();

            // Render (traversed) values from the request in the breadcrumb title and route parameters
            if (null !== $request) {
                preg_match_all('#\{(?P<variable>\w+).?(?P<function>([\w\.])*):?(?P<parameters>(\w|,| )*)\}#', $breadcrumbOrTitle, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $varName = $match['variable'][0];

                    if (false === $request->attributes->has($varName)) {
                        continue;
                    }
                    $object = $request->attributes->get($varName);

                    $breadcrumbOrTitle = $this->renderObjectValuesInSubject($match, $object, $varName, $breadcrumbOrTitle);
                }

                foreach ($routeParameters as $key => $parameterValue) {
                    if (is_numeric($key)) {
                        $routeParameters[$parameterValue] = $request->attributes->get($parameterValue);
                        unset($routeParameters[$key]);

                        continue;
                    }

                    if (!\is_string($parameterValue)) {
                        continue;
                    }

                    if (preg_match_all('#\{(?P<variable>\w+).?(?P<function>([\w\.])*):?(?P<parameters>(\w|,| )*)\}#', $parameterValue, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $varName = $match['variable'][0];

                            if (false === $request->attributes->has($varName)) {
                                continue;
                            }
                            $object = $request->attributes->get($varName);

                            $routeParameters[$key] = $this->renderObjectValuesInSubject($match, $object, $varName, $parameterValue);
                        }
                    } elseif (preg_match('#^\{(?P<parameter>\w+)\}$#', $parameterValue, $matches)) {
                        $routeParameters[$key] = $request->attributes->get($matches['parameter']);
                    }
                }
            }

            $url = null;
            if (null !== $routeName) {
                $referenceType = $routeAbsolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::RELATIVE_PATH;
                $url = $this->router->generate($routeName, $routeParameters, $referenceType);
            }

            $breadcrumb = new Breadcrumb($breadcrumbOrTitle, $url, $attributes);
        }

        if (0 === $position || $position > $this->breadcrumbs->count()) {
            $this->breadcrumbs->offsetSet($breadcrumb);
        } else {
            $this->insert($breadcrumb, $position);
        }

        return $this;
    }

    private function insert(Breadcrumb $breadcrumb, int $position): void
    {
        if ($position < 0) {
            $position += $this->breadcrumbs->count();
        } else { // $position >= 1
            --$position;
        }

        $breadcrumbs = new \SplObjectStorage();
        $breadcrumbs->addAll($this->breadcrumbs);
        $this->breadcrumbs->removeAll($this->breadcrumbs);

        $breadcrumbs->rewind();
        while ($breadcrumbs->valid()) {
            if (max(0, $position) == $breadcrumbs->key()) {
                $this->breadcrumbs->offsetSet($breadcrumb);
            }

            $this->breadcrumbs->offsetSet($breadcrumbs->current());
            $breadcrumbs->next();
        }
    }

    public function reset(): self
    {
        $this->breadcrumbs->removeAll($this->breadcrumbs);

        return $this;
    }

    public function count(): int
    {
        return $this->breadcrumbs->count();
    }

    public function getIterator(): \Traversable
    {
        $this->breadcrumbs->rewind();

        return $this->breadcrumbs;
    }

    /**
     * Render all variables, parameters and function calls in the renderSubject by iteratively traversing an object graph tree.
     *
     * Will eventually return `title` from `organization` when the breadcrumb contains `{organization.author.book.title}`.
     */
    private function renderObjectValuesInSubject(array $match, mixed $object, string $varName, string $renderSubject): string
    {
        $functions = $match['function'][0] ? explode('.', $match['function'][0]) : [];
        $parameters = $match['parameters'][0] ? explode(',', $match['parameters'][0]) : [];
        $nbCalls = \count($functions);

        // Eventually the last function is the one that is needed to retrieve the actual object value for
        foreach ($functions as $f => $function) {
            // While this is not the last function, call the chain
            if ($f < $nbCalls - 1) {
                $object = $this->retrieveChildObject($object, $function, $varName, $functions);

                continue;
            }
            $objectValue = $this->retrieveObjectValue($object, $function, $parameters, $varName, $functions);
        }

        if (!isset($objectValue)) {
            $objectValue = (string) $object;
        }

        return str_replace($match[0][0], $objectValue, $renderSubject);
    }

    /**
     * Allows retrieving the next child object by calling the related method.
     *
     * Gets used in case breadcrumb values are splitted by dots (e.g. `{organization.author.book.title}`).
     */
    private function retrieveChildObject(mixed $object, string $function, string $varName, array $functions): mixed
    {
        if (\is_callable([$object, $fullFunctionName = 'get'.$function])
            || \is_callable([$object, $fullFunctionName = 'has'.$function])
            || \is_callable([$object, $fullFunctionName = 'is'.$function])
        ) {
            return \call_user_func([$object, $fullFunctionName]);
        }

        throw new \RuntimeException(sprintf('"%s" is not callable.', implode('.', array_merge([$varName], $functions))));
    }

    /**
     * Allow to finally retrieve the value in case the last method was reached.
     *
     * Gets used once the splitted breadcrumb value reached the end of the call stack (e.g. for `title`
     * when `{organization.author.book.title}` gets requested).
     */
    private function retrieveObjectValue(mixed $object, string $function, array $parameters, string $varName, array $functions): mixed
    {
        if (\is_callable([$object, $fullFunctionName = 'get'.$function])
            || \is_callable([$object, $fullFunctionName = 'has'.$function])
            || \is_callable([$object, $fullFunctionName = 'is'.$function])) {
            return \call_user_func_array([$object, $fullFunctionName], $parameters);
        }

        throw new \RuntimeException(sprintf('"%s" is not callable.', implode('.', array_merge([$varName], $functions))));
    }
}

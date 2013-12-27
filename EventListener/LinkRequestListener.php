<?php

/**
 *
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 * @see http://williamdurand.fr/2012/08/02/rest-apis-with-symfony2-the-right-way/ <REST LINK Implementation>
 *
 */

namespace Tms\Bundle\RestBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class LinkRequestListener
{
    /**
     * @var ControllerResolverInterface
     */
    private $resolver;
    /**
     *
     * @var UrlMatcherInterface
     */
    private $urlMatcher;
    private $managers = array();

    /**
     * @param ControllerResolverInterface $controllerResolver The 'controller_resolver' service
     * @param UrlMatcherInterface         $urlMatcher         The 'router' service
     */
    public function __construct(ControllerResolverInterface $controllerResolver, UrlMatcherInterface $urlMatcher)
    {
        $this->resolver = $controllerResolver;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * Add a manager to the available managers, indexed by the entity class
     *
     * @param string $class
     * @param Object $manager
     */
    public function addManager($class, $manager)
    {
        $this->managers[$class] = $manager;
    }

    /**
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->getRequest()->headers->has('link')) {
            return;
        }

        $links = $this->getRequestLinksFromHeaders($event);
        $requestMethod = $this->urlMatcher->getContext()->getMethod();

        // Force the GET method to avoid the use of the previous method (LINK/UNLINK)
        $this->urlMatcher->getContext()->setMethod('GET');

        // The controller resolver needs a request to resolve the controller.
        $stubRequest = new Request();

        foreach ($links as $index => $link) {
            $links[$index] = null;
            if (false === $route = $this->checkAndGetRoute($link)) {
                continue;
            }
            if (false === $relation = $this->checkAndGetRelation($link)) {
                continue;
            }
            $stubRequest->attributes->replace($route);
            if (false === $controller = $this->resolver->getController($stubRequest)) {
                continue;
            }

            $subEvent = new FilterControllerEvent($event->getKernel(), $controller, $stubRequest, HttpKernelInterface::MASTER_REQUEST);
            $event->getDispatcher()->dispatch(KernelEvents::CONTROLLER, $subEvent);
            $controller = $subEvent->getController();

            $arguments = $this->resolver->getArguments($stubRequest, $controller);

            try {
                $result = call_user_func_array($controller, $arguments);
                $entityDecoded = json_decode($result->getContent(), true);
                $links[$index] = $this->getManagerByClassName($entityDecoded['class'])->findOneById($entityDecoded['data']['id']);
            } catch (\Exception $e) {
                continue;
            }
        }

        $event->getRequest()->attributes->set('links', $links);
        $this->urlMatcher->getContext()->setMethod($requestMethod);
    }

    /**
     * Get the manager of the given class name
     *
     * @param string $className
     * @throws \Exception
     * @return Object
     */
    private function getManagerByClassName($className)
    {
        if (!isset($this->managers[$className])) {
            throw new \Exception(sprintf('Manager of class %s not found', $className));
        }

        return $this->managers[$className];
    }

    /**
     * Extract the links in the request headers
     *
     * @param GetResponseEvent $event
     * @return array
     */
    private function getRequestLinksFromHeaders(GetResponseEvent $event)
    {
        $links = array();
        $header = $event->getRequest()->headers->get('link');

        /*
         * Due to limitations, multiple same-name headers are sent as comma
        * separated values.
        *
        * This breaks those headers into Link headers following the format
        * http://tools.ietf.org/html/rfc2068#section-19.6.2.4
        */
        while (preg_match('/^((?:[^"]|"[^"]*")*?),/', $header, $matches)) {
            $header  = trim(substr($header, strlen($matches[0])));
            $links[] = $matches[1];
        }

        if ($header) {
            $links[] = $header;
        }

        return $links;
    }

    /**
     * Get the resource route of a Link
     *
     * @param string $link
     * @return boolean|string
     */
    private function checkAndGetRoute($link)
    {
        $linkExploded = explode(';', trim($link));
        $resource = array_shift($linkExploded);
        $resource = preg_replace('/<|>/', '', $resource);
        try {
            $route = $this->urlMatcher->match($resource);
        } catch (\Exception $e) {
            // If we don't have a matching route we return false
            return false;
        }

        return $route;
    }

    /**
     * Get the resource relation of a link
     *
     * @param string $link
     * @return boolean|string
     */
    private function checkAndGetRelation($link)
    {
        $linkExploded = explode(';', trim($link));
        $relation = array_pop($linkExploded);
        if (!preg_match('/^rel=\"[_a-z]*\"$/', trim($relation), $matches)) {
            // If the relation is not well formated, we return false
            return false;
        }
        $relation = str_replace(array('rel=', '"'), '', $matches[0]);

        return $relation;
    }
}

<?php
namespace Midgard\AppServerBundle\AiP;

require __DIR__.'/../../../../app/AppKernel.php';

use Symfony\Component\HttpFoundation\Request;
use Midgard\AppServerBundle\AiP\Response as AiPResponse;

class Application
{
    /**
     * @var Symfony\Component\HttpKernel\Kernel
     */
    private static $kernel;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct()
    {
        if (self::$kernel) {
            return;
        }
        self::$kernel = new \AppKernel('dev', false);
        self::$kernel->loadClassCache();
    }

    /**
     * Invoke is run once per each request. Here we generate a
     * Request object, tell Symfony2 to handle it, and eventually
     * return the Result contents back to AiP
     */
    public function __invoke($context)
    {
        // Prepare Request object
        $request = $this->ctx2Request($context);

        $response = self::$kernel->handle($request);

        foreach ($response->headers->getCookies() as $cookie) {
            $context['_COOKIE']->setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        return array($response->getStatusCode(), $this->getHeaders($response), $response->getContent());
    }

    private function ctx2Request($context)
    {
        $uri = "http://{$context['env']['HTTP_HOST']}{$context['env']['REQUEST_URI']}";
        $method = $context['env']['REQUEST_METHOD'];
        $server = $context['env'];

        $parameters = array();
        $files = array();
        if ($method == 'POST') {
            $parameters = $context['_POST'];
            $files = $context['_FILES'];
        }
        $cookies = $context['_COOKIE']->__toArray();

        return Request::create($uri, $method, $parameters, $cookies, $files);
    }

    /**
     * Normalize headers from a Symfony2 Response ParameterBag
     * to the array used by AiP
     */
    private function getHeaders($response)
    {
        $ret = array();
        $headers = $response->headers->all();
        foreach ($headers as $header => $values) {
            $ret[] = $header;
            $ret[] = implode(';', $values);
        }
        return $ret;
    }

    /**
     * Cast the Response into an AiP Response which doesn't
     * attempt to send output on its own
     */
    public function onCoreResponse($event)
    {
        $response = $event->getResponse();
        $newResponse = new AiPResponse($response->getContent(), $response->getStatusCode(), $response->headers->all());
        $event->setResponse($newResponse);
    }
}

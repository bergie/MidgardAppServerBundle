<?php
namespace Midgard\AppServerBundle\AiP;

require_once __DIR__.'/../../../../app/bootstrap.php.cache';
require_once __DIR__.'/../../../../app/AppKernel.php';

use Symfony\Component\HttpFoundation\Request;
use Midgard\AppServer\AiP\Response as AiPResponse;
use AiP\Middleware\HTTPParser;
use AiP\Middleware\Session;
use AiP\Middleware\URLMap;
use AiP\Middleware\Logger;

class Application
{
    /**
     * @var Symfony\Component\HttpFoundation\Response
     */
    private $response;

    /**
     * @var Symfony\Component\HttpKernel\Kernel
     */
    private $kernel;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct()
    {
        $urlmap = array();
        $urlmap['/'] = new HTTPParser(new Session($this));
        // TODO: Add a fileserver for files in web directory
        $map = new URLMap($urlmap);

        $appServer = new Logger($map, STDOUT);

        $this->kernel = new \AppKernel('prod', false);
        $this->kernel->loadClassCache();
    }

    /**
     * Invoke is run once per each request. Here we generate a
     * Request object, tell Symfony2 to handle it, and eventually
     * return the Result contents back to AiP
     */
    public function __invoke($context)
    {
        call_user_func($context['logger'], "Serving request to {$context['env']['REQUEST_URI']}");

        // Prepare Request object
        $request = Request::create($context['env']['REQUEST_URI'], $context['env']['REQUEST_METHOD']);

        $this->response = null;

        try {
            $this->kernel->handle($request)->send();
        }
        catch (\Exception $e) {
            call_user_func($context['logger'], "[Exception] " . $e->getMessage());
            return array(500, array(), $e->getMessage());
        }
        call_user_func($context['logger'], "Status " . $this->response->getStatusCode());

        return array($this->response->getStatusCode(), $this->getHeaders($this->response), $this->response->getContent());
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
     * Get a reference of the Response object so it can be later
     * used for the return value of __invoke
     */
    public function onCoreView($event)
    {
        $this->response = $event->getControllerResult();
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

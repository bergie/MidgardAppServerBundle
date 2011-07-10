<?php
namespace Midgard\AppServerBundle\AiP;

require_once __DIR__.'/../../../../app/AppKernel.php';

use Symfony\Component\HttpFoundation\Request;
use Midgard\AppServerBundle\AiP\Response as AiPResponse;
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
        $this->log($context, "Serving request to {$context['env']['REQUEST_URI']}");

        // Prepare Request object
        $request = Request::create($context['env']['REQUEST_URI'], $context['env']['REQUEST_METHOD']);

        $response = $this->kernel->handle($request);

        return array($response->getStatusCode(), $this->getHeaders($response), $response->getContent());
    }

    private function log($context, $message)
    {
        call_user_func($context['logger'], $message);
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

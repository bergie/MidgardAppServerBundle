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
    private $kernel;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct()
    {
        $this->kernel = new \AppKernel('dev', false);
        $this->kernel->loadClassCache();
    }

    /**
     * Invoke is run once per each request. Here we generate a
     * Request object, tell Symfony2 to handle it, and eventually
     * return the Result contents back to AiP
     */
    public function __invoke($context)
    {
        // Prepare Request object
        // TODO: Set possible request parameters etc.
        $request = Request::create($context['env']['REQUEST_URI'], $context['env']['REQUEST_METHOD']);

        $response = $this->kernel->handle($request);

        return array($response->getStatusCode(), $this->getHeaders($response), $response->getContent());
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

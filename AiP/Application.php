<?php
namespace Midgard\AppServerBundle\AiP;

require_once __DIR__.'/../../../../app/bootstrap.php.cache';
require_once __DIR__.'/../../../../app/AppKernel.php';

use Symfony\Component\HttpFoundation\Request;
use Midgard\AppServer\AiP\Response;

class Application
{
    private $appServer = null;
    private $response = null;
    private $kernel = null;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct()
    {
        $urlmap = array();
        $urlmap['/'] = new \AiP\Middleware\HTTPParser(new \AiP\Middleware\Session($this));
        $map = new \AiP\Middleware\URLMap($urlmap);

        $this->appServer = new \AiP\Middleware\Logger($map, STDOUT);

        $this->kernel = new \AppKernel('prod', false);
        $this->kernel->loadClassCache();
    }

   /**
     * Invoke is run once per each request.
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

    public function onCoreView($event)
    {
        $response = $event->getControllerResult();
        $converter = new \Silex\StringResponseConverter();
        $this->response = $converter->convert($response);
    }

    public function onCoreResponse($event)
    {
        $response = $event->getResponse();
        $newResponse = new Midgard\AppServerBundle\AiP\Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
        $event->setResponse($newResponse);
    }
}

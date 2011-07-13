<?php
namespace Midgard\AppServerBundle\AiP;

use Symfony\Component\HttpFoundation\Request;

class Application
{
    /**
     * @var Symfony\Component\HttpKernel\Kernel
     */
    private $kernel;

    private $prefix;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct(array $config)
    {
        require __DIR__ . "/../../../../app/{$config['kernelFile']}";
        $kernelClass = "\\{$config['kernel']}";

        $this->kernel = new $kernelClass($config['environment'], false);
        $this->kernel->loadClassCache();

        $this->prefix = $config['path'];
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
        $response = $this->kernel->handle($request);

        foreach ($response->headers->getCookies() as $cookie) {
            $context['_COOKIE']->setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        return array($response->getStatusCode(), $this->getHeaders($response), $response->getContent());
    }

    private function ctx2Request($context)
    {
        $requestUri = $context['env']['REQUEST_URI'];
        if (   strlen($this->prefix) > 1
            && substr($requestUri, 0, strlen($this->prefix)) == $this->prefix) {
            $requestUri = substr($requestUri, strlen($this->prefix));
        }

        $uri = "http://{$context['env']['HTTP_HOST']}{$requestUri}";
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
}

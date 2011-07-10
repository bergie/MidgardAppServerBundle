<?php
namespace Midgard\AppServerBundle\AiP;

use Midgard\AppServerBundle\AiP\Application;
use AiP\App\FileServe;
use AiP\Middleware\HTTPParser;
use AiP\Middleware\Session;
use AiP\Middleware\URLMap;
use AiP\Middleware\Logger;

class Runner
{
    /**
     * @var AiP\Middleware\Logger
     */
    private $app;

    /**
     * Construct prepares the AppServer in PHP URL mappings
     * and is run once. It also loads the Symfony Application kernel
     */
    public function __construct()
    {
        $urlmap = array();
        $urlmap['/'] = new HTTPParser(new Session(new Application()));
        $urlmap['/favicon.ico'] = function($ctx) { return array(404, array(), ''); };
        $urlmap['/bundles'] = new FileServe(realpath(__DIR__.'/../../../../web/bundles'), 4000000);
        $map = new URLMap($urlmap);

        $this->app = new Logger($map, STDOUT);
    }

    /**
     * Invoke is run once per each request. Here we generate a
     * Request object, tell Symfony2 to handle it, and eventually
     * return the Result contents back to AiP
     */
    public function __invoke($context)
    {
        $app = $this->app;
        return $app($context);
    }
}

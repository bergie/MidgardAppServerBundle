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
        $symfony_root = realpath(__DIR__.'/../../../..');
        $aip_config = "{$symfony_root}/" . array_pop($_SERVER['argv']);
        if (!file_exists($aip_config)) {
            throw new \Exception("No config file '{$aip_config}' found");
        }
        $config = \pakeYaml::loadFile($aip_config);
        if (!isset($config['symfony.kernels'])) {
            throw new \Exception("No symfony.kernels configured in {$aip_config}");
        }

        $urlmap = array();

        foreach ($config['symfony.kernels'] as $kernel) {
            $urlmap[$kernel['path']] = new HTTPParser(new Session(new Application($kernel['kernel'], $kernel['environment'])));
        }

        $urlmap['/favicon.ico'] = function($ctx) { return array(404, array(), ''); };

        $urlmap = $this->addFileServers("{$symfony_root}/web", $urlmap); 

        $map = new URLMap($urlmap);

        $this->app = new Logger($map, STDOUT);
    }

    private function addFileServers($web_root, array $urlmap)
    {
        $web_dirs = scandir($web_root);
        foreach ($web_dirs as $web_dir) {
            if (substr($web_dir, 0, 1) == '.') {
                continue;
            }
            if (!is_dir("{$web_root}/{$web_dir}")) {
                continue;
            }
            $urlmap["/{$web_dir}"] = new FileServe("{$web_root}/{$web_dir}", 4000000);
        }
        return $urlmap;
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

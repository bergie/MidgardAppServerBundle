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
        $symfonyRoot = realpath(__DIR__.'/../../../..');
        $config = $this->loadConfig($symfonyRoot);

        $urlMap = array();

        $urlMap['/favicon.ico'] = function($ctx) { return array(404, array(), ''); };

        $urlMap = array_merge($urlMap, $this->addKernels($config));
        $urlMap = array_merge($urlMap, $this->addFileServers("{$symfonyRoot}/web")); 

        $map = new URLMap($urlMap);

        $this->app = new Logger($map, STDOUT);
    }

    private function loadConfig($symfonyRoot)
    {
        $aipConfig = "{$symfonyRoot}/" . array_pop($_SERVER['argv']);
        if (!file_exists($aipConfig)) {
            throw new \Exception("No config file '{$aipConfig}' found");
        }
        return \pakeYaml::loadFile($aipConfig);
    }

    private function addKernels(array $config)
    {
        $urlMap = array();
        if (!isset($config['symfony.kernels'])) {
            throw new \Exception("No symfony.kernels configured in {$aipConfig}");
        }

        foreach ($config['symfony.kernels'] as $kernel) {
            $urlMap[$kernel['path']] = new HTTPParser(new Session(new Application($kernel['kernel'], $kernel['kernelFile'], $kernel['environment'])));
        }

        return $urlMap;
    }

    private function addFileServers($webRoot)
    {
        $urlMap = array();
        $webDirs = scandir($webRoot);
        foreach ($webDirs as $webDir) {
            if (substr($webDir, 0, 1) == '.') {
                continue;
            }
            if (!is_dir("{$webRoot}/{$webDir}")) {
                continue;
            }
            $urlMap["/{$webDir}"] = new FileServe("{$webRoot}/{$webDir}", 4000000);
        }
        return $urlMap;
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

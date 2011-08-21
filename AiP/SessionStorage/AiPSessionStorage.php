<?php
namespace Midgard\AppServerBundle\AiP\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use AiP\Middleware\Session\LogicException;

class AiPSessionStorage extends NativeSessionStorage
{
    private $path;
    private $started;
    private $context = null;

    /**
     * Constructor.
     */
    public function __construct($path, array $options = array())
    {
        $this->path = $path;
        $this->started = false;
    }

    public function setContext(array $context)
    {
        if (!isset($context['mfs.session'])) {
            throw \RuntimeException("AiP Session middleware not available");
        }
        $this->context = $context;
    }

    public function commitSession()
    {
        if (!$this->started) {
            return;
        }

        try {
            $this->context['mfs.session']->save();
        } catch (LogicException $e) {
        }
    }

    /**
     * Starts the session.
     *
     * @api
     */
    public function start()
    {
        if ($this->started) {
            return;
        }

        $options = array(
            'save_path' => $this->path,
            'cookie_name' => 'Sf2Session',
        );
        if (isset($this->options['lifetime'])) {
            $options['cookie_lifetime'] = $this->options['lifetime'];
        }
        /*
            'cookie_path' => $this->options['path'],
            'cookie_domain' => $this->options['domain'],
            'cookie_secure' => $this->options['secure'],
            'cookie_httponly' => $this->options['httponly'],
            'save_path' => $this->path,
        );*/

        if (!file_exists($this->path)) {
            mkdir($this->path);
        }

        $this->context['mfs.session']->start($options);

        $this->started = true;
    }

    /**
     * Returns the session ID
     *
     * @return mixed  The session ID
     *
     * @throws \RuntimeException If the session was not started yet
     *
     * @api
     */
    public function getId()
    {
        return $this->context['mfs.session']->getId();
    }

    /**
     * Reads data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param  string $key  A unique key identifying your data
     *
     * @return mixed Data associated with the key
     *
     * @throws \RuntimeException If an error occurs while reading data from this storage
     *
     * @api
     */
    public function read($key, $default = null)
    {
        if (!$this->started) {
            return $default;
        }
        if (!isset($this->context['mfs.session']->$key)) {
            return $default;
        }
        return $this->context['mfs.session']->$key;
    }

    /**
     * Removes data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param  string $key  A unique key identifying your data
     *
     * @return mixed Data associated with the key
     *
     * @throws \RuntimeException If an error occurs while removing data from this storage
     *
     * @api
     */
    public function remove($key)
    {
        $data = $this->context['mfs.session']->$key;
        unset($this->context['mfs.session']->$key);
        return $data;
    }

    /**
     * Writes data to this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param  string $key   A unique key identifying your data
     * @param  mixed  $data  Data associated with your key
     *
     * @throws \RuntimeException If an error occurs while writing to this storage
     *
     * @api
     */
    public function write($key, $data)
    {
        if (!$this->started) {
            throw new \RuntimeException("Can't write to a session before it is started");
        }
        $this->context['mfs.session']->$key = $data;
    }

    /**
     * Regenerates id that represents this storage.
     *
     * @param  Boolean $destroy Destroy session when regenerating?
     *
     * @return Boolean True if session regenerated, false if error
     *
     * @throws \RuntimeException If an error occurs while regenerating this storage
     *
     * @api
     */
    public function regenerate($destroy = false)
    {
        return true;
    }
}

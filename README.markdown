# Symfony2 AppServer-in-PHP Bundle

Enables running Symfony2 applications with the [AppServer-in-PHP](https://github.com/indeyets/appserver-in-php) (AiP). AiP simplifies application deployment greatly by not requiring a normal web server, and makes Symfony2 operations more efficient by keeping the framework persistent between requests.

Users of other PHP frameworks like Midgard MVC or Flow3 have reported performance increases between 2 and 4 times faster by running their systems on AiP instead of the traditional way of serving PHP.

## How it works

AiP is an application server for PHP applications that has been written in PHP itself. You can either run it as a full HTTP server, in which situation no regular web server like Apache is needed. Or you can set it up to be a FastCGI provider if you run more than just PHP on your server.

### Integration with Symfony2

The AppServerBundle integrates AiP with the Symfony2 framework. The points of integration are the following:

* Your Symfony2 application is started by the `aip` command, which launches the AppServerBundle Application
* The Application bootstraps Symfony2 and creates an instance of the Symfony2 AppKernel
* When AiP receives a request, the `__invoke` method of the Application is run, generating a regular Symfony2 Request object and telling AppKernel to Handle it
* On the `kernel.view` event, the Application takes a reference to the Symfony2 Response object
* On the `kernel.response` event, the Application replaces the regular Symfony2 Response object with its own Response object which does not attempt to send output
* When request handling has completed the status code, headers, and contents of the Response object are passed to AiP
* AiP sends the response to the browser

## Installation

### Dependencies

Install AppServer-in-PHP either via PEAR:

    $ pear channel-discover pear.indeyets.pp.ru
    $ pear install indeyets/aip

...or by installing the AiP repository under your `vendors` directory. AiP requires the `php-cli` package to run, as it is basically a command-line PHP script.

### AppServerBundle

Install this bundle under your `vendors` directory and add the `Midgard` namespace to your `autoload.php`.

Enable this bundle by adding `new Midgard\AppServerBundle\MidgardAppServerBundle()` to your `AppKernel.php`.

### Application server configuration

Copy the `aip.yaml.example` file from this bundle to your `app` directory. You can edit it as necessary. By default it sets up two workers to listen to `http://localhost:8001`.

## Usage

Start your server with:

    $ aip app app/aip.yaml

## Contributing

The AppServerBundle is still under heavy development, and is so not likely to support all of Symfony2's feature set. Contributions for making it more reliable are very welcome.

Just fork the AppServerBundle repository on GitHub and send pull requests.

## Credits

The Symfony2 AppServerBundle has been written by [Henri Bergius](http://bergie.iki.fi/). AppServer-in-PHP (AiP) has been written by [Alexey Zakhlestin](https://github.com/indeyets).

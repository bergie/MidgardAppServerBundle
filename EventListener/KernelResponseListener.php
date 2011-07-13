<?php
namespace Midgard\AppServerBundle\EventListener;

use Midgard\AppServerBundle\AiP\Response as AiPResponse;

class KernelResponseListener
{
    /**
     * Cast the Response into an AiP Response which doesn't
     * attempt to send output on its own
     */
    public function onKernelResponse($event)
    {
        $response = $event->getResponse();
        $newResponse = new AiPResponse($response->getContent(), $response->getStatusCode(), $response->headers->all());
        $event->setResponse($newResponse);
    }
}

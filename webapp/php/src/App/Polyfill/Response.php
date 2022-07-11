<?php
declare(strict_types=1);

namespace App\Polyfill;

class Response extends \Slim\Psr7\Response
{
    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $body = $this->getBody();
        $body->write($json = json_encode($data, $encodingOptions));

        // Ensure that the json encoding passed successfully
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }
        $response = (clone $this)->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        if (isset($status)) {
            return $response->withStatus($status);
        }
        return $response;
    }
}
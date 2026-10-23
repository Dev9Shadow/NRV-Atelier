<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServiceBillet;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetBilletAction
{
    public function __construct(private ServiceBillet $serviceBillet) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = [
            'type' => 'resource',
            'billet' => $this->serviceBillet->getBillet($args['token']),
            'links' => ['self' => ['href' => '/billets/' . $args['token']]],
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}

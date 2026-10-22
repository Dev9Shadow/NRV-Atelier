<?php

namespace nrv\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use nrv\core\application\usecases\ServiceAuth;

class SignupAction
{
    private ServiceAuth $service;

    public function __construct(ServiceAuth $service)
    {
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $dto = $this->service->signup($data);

        $response->getBody()->write(json_encode($dto->toArray()));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
<?php

namespace nrv\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use nrv\core\application\usecases\ServiceAuth;
use nrv\core\domain\exceptions\ValidationException;

class SigninAction
{
    private ServiceAuth $service;

    public function __construct(ServiceAuth $service)
    {
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        if (empty($data['email']) || empty($data['password'])) {
            throw new ValidationException("Email et mot de passe requis");
        }

        $result = $this->service->signin($data['email'], $data['password']);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
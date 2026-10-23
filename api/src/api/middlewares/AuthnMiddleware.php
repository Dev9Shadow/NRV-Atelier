<?php

namespace nrv\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use nrv\core\application\ports\AuthTokenProviderInterface;
use nrv\core\domain\exceptions\NonAuthentifieException;

class AuthnMiddleware implements MiddlewareInterface
{
    private AuthTokenProviderInterface $tokenProvider;

    public function __construct(AuthTokenProviderInterface $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            throw new NonAuthentifieException("Token manquant ou invalide");
        }

        $token = $matches[1];
        $userDTO = $this->tokenProvider->decodeToken($token);

        if (!$userDTO) {
            throw new NonAuthentifieException("Token expiré ou invalide");
        }

        $request = $request->withAttribute('utilisateur', $userDTO);
        return $handler->handle($request);
    }
}
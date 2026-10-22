<?php

namespace nrv\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use nrv\core\domain\exceptions\AccesInterditException;

class AuthzAdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('utilisateur');
        if (!$user || $user->role !== 'ADMIN') {
            throw new AccesInterditException("Accès réservé aux administrateurs");
        }

        return $handler->handle($request);
    }
}
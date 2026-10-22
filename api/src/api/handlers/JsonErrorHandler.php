<?php

namespace nrv\api\handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Throwable;
use nrv\core\domain\exceptions\ValidationException;
use nrv\core\domain\exceptions\NonAuthentifieException;
use nrv\core\domain\exceptions\AccesInterditException;
use nrv\core\domain\exceptions\EntityNotFoundException; 
use nrv\core\domain\exceptions\PlacesInsuffisantesException;
use nrv\core\domain\exceptions\TransitionStatutException;

class JsonErrorHandler
{
    private $app;
    private bool $displayErrorDetails;

    public function __construct($app, bool $displayErrorDetails)
    {
        $this->app = $app;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $statusCode = 500;
        $message = "Erreur interne du serveur";

        if ($exception instanceof ValidationException) {
            $statusCode = 400;
            $message = $exception->getMessage();
        } elseif ($exception instanceof NonAuthentifieException) {
            $statusCode = 401;
            $message = $exception->getMessage();
        } elseif ($exception instanceof AccesInterditException) {
            $statusCode = 403;
            $message = $exception->getMessage();
        } elseif ($exception instanceof EntityNotFoundException) {
            $statusCode = 404;
            $message = $exception->getMessage();
        } elseif ($exception instanceof PlacesInsuffisantesException || $exception instanceof TransitionStatutException) {
            $statusCode = 409;
            $message = $exception->getMessage();
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage();
        } elseif ($this->displayErrorDetails) {
            $message = $exception->getMessage();
        }

        $payload = [
            "type" => "error",
            "code" => $statusCode,
            "message" => $message
        ];

        if ($this->displayErrorDetails) {
            $payload["trace"] = $exception->getTraceAsString();
        }

        $response = $this->app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
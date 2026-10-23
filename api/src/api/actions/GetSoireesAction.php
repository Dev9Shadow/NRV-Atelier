<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServiceCatalogue;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetSoireesAction
{
    public function __construct(private ServiceCatalogue $catalogue) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $soirees = $this->catalogue->listerSoirees();

        $data = [
            'type' => 'collection',
            'count' => count($soirees),
            'soirees' => array_map(fn(array $soiree) => [
                'soiree' => $soiree,
                'links' => ['self' => ['href' => '/soirees/' . $soiree['id']]],
            ], $soirees),
            'links' => ['self' => ['href' => '/soirees']],
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}

<?php
namespace nrv\api\actions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use nrv\core\application\usecases\ServiceCatalogue;

class GetSpectaclesAction {
    private ServiceCatalogue $catalogue;

    public function __construct(ServiceCatalogue $catalogue) {
        $this->catalogue = $catalogue;
    }

    public function __invoke(Request $request, Response $response, array $args): Response {
        $queryParams = $request->getQueryParams();

        $spectacles = $this->catalogue->listerSpectacles($queryParams);

        $data = [
            "type" => "collection",
            "count" => count($spectacles),
            "spectacles" => []
        ];

        foreach ($spectacles as $spec) {
            $data["spectacles"][] = [
                "spectacle" => [
                    "id" => $spec['id'],
                    "titre" => $spec['titre'],
                    "date" => $spec['date'],
                    "horaire" => $spec['horaire'],
                    "image" => $spec['image'],
                    "style" => $spec['style']
                ],
                "links" => [
                    "self" => ["href" => "/spectacles/" . $spec['id']],
                    "soiree" => ["href" => "/soirees/" . $spec['id_soiree']]
                ]
            ];
        }

        $queryString = $request->getUri()->getQuery();
        $selfUrl = "/spectacles" . ($queryString ? '?' . $queryString : '');
        $data["links"]["self"]["href"] = $selfUrl;

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
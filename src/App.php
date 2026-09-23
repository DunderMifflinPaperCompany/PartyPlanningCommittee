<?php

declare(strict_types=1);

namespace PartyPlanningCommittee;

use PartyPlanningCommittee\Http\CsrfGuard;
use PartyPlanningCommittee\Http\PartyController;
use PartyPlanningCommittee\Http\Router;
use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PDO;

/**
 * Belsnickel assembles the committee. One wiring point, so no impish corner of the
 * application may quietly build its own private router.
 */
final class App
{
    public static function router(PDO $pdo, View $view, CsrfGuard $csrf): Router
    {
        $controller = new PartyController(
            new PartyRepository($pdo),
            new RsvpRepository($pdo),
            new OfficeRepository(),
            $view,
            $csrf
        );

        $router = new Router();
        $router->get('/', static fn (array $params, array $input) => $controller->index());
        $router->get('/upcoming', static fn (array $params, array $input) => $controller->upcoming());
        $router->get('/parties/new', static fn (array $params, array $input) => $controller->newParty());
        $router->get('/parties/{id}', static fn (array $params, array $input) => $controller->show($params));
        $router->get('/offices/{slug}', static fn (array $params, array $input) => $controller->office($params));
        $router->post('/parties', static fn (array $params, array $input) => $controller->create($params, $input));
        $router->post('/parties/{id}/rsvps', static fn (array $params, array $input) => $controller->rsvp($params, $input));
        $router->post('/parties/{id}/cancel', static fn (array $params, array $input) => $controller->cancel($params, $input));

        return $router;
    }

    public static function viewDirectory(): string
    {
        return dirname(__DIR__) . '/views';
    }
}

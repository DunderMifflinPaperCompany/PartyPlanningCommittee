<?php

declare(strict_types=1);

/**
 * Belsnickel's front door. Every request enters here and leaves judged.
 */

use PartyPlanningCommittee\App;
use PartyPlanningCommittee\Http\CsrfGuard;
use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Repository\Database;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PartyPlanningCommittee\Repository\Seeder;

require_once dirname(__DIR__) . '/bootstrap.php';

$databasePath = getenv('PPC_DATABASE') !== false
    ? (string) getenv('PPC_DATABASE')
    : dirname(__DIR__) . '/data/committee.sqlite';

$pdo = Database::file($databasePath);

// Admirable: the ledger seeds itself once so the first visit is not a barren page.
Seeder::seed(new PartyRepository($pdo), new RsvpRepository($pdo));

$view = new View(App::viewDirectory());
$csrf = CsrfGuard::fromSession();
$router = App::router($pdo, $view, $csrf);

$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = (string) ($_SERVER['REQUEST_URI'] ?? '/');

// Belsnickel reads POST bodies only for POST requests. Anything looser would be impish.
$input = $method === 'POST' ? $_POST : [];

$router->dispatch($method, $path, $input)->send();

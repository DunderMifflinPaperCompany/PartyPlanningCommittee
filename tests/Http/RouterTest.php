<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use PartyPlanningCommittee\Http\Response;
use PartyPlanningCommittee\Http\Router;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the router, because a route that guesses is an impish route.
 */
final class RouterTest extends TestCase
{
    public function testItMatchesAStaticRoute(): void
    {
        $router = new Router();
        $router->get('/', static fn (): string => 'home');

        $match = $router->match('GET', '/');

        $this->assertNotNull($match);
        $this->assertSame([], $match['params']);
    }

    public function testItExtractsNamedParameters(): void
    {
        $router = new Router();
        $router->get('/parties/{id}', static fn (): string => 'party');

        $match = $router->match('GET', '/parties/42');

        $this->assertNotNull($match);
        $this->assertSame(['id' => '42'], $match['params']);
    }

    public function testItRespectsTheHttpMethod(): void
    {
        $router = new Router();
        $router->post('/parties', static fn (): string => 'created');

        $this->assertNull($router->match('GET', '/parties'));
        $this->assertNotNull($router->match('post', '/parties'));
    }

    public function testItPrefersTheFirstRegisteredRoute(): void
    {
        $router = new Router();
        $router->get('/parties/new', static fn (): string => 'form');
        $router->get('/parties/{id}', static fn (): string => 'party');

        $this->assertSame('form', $router->dispatch('GET', '/parties/new')->body());
        $this->assertSame('party', $router->dispatch('GET', '/parties/7')->body());
    }

    public function testParametersNeverSwallowASlash(): void
    {
        $router = new Router();
        $router->get('/parties/{id}', static fn (): string => 'party');

        $this->assertNull($router->match('GET', '/parties/7/rsvps'));
    }

    public function testItIgnoresTrailingSlashesAndQueryStrings(): void
    {
        $router = new Router();
        $router->get('/upcoming', static fn (): string => 'soon');

        $this->assertSame('soon', $router->dispatch('GET', '/upcoming/')->body());
        $this->assertSame('soon', $router->dispatch('GET', '/upcoming?office=utica')->body());
    }

    public function testItReturnsANotFoundResponseForAnUnknownPath(): void
    {
        $response = (new Router())->dispatch('GET', '/stamford');

        $this->assertSame(404, $response->status());
    }

    public function testItPassesInputToTheHandler(): void
    {
        $router = new Router();
        $router->post('/parties/{id}/rsvps', static function (array $params, array $input): Response {
            return Response::html($params['id'] . ':' . (string) $input['employee_name']);
        });

        $response = $router->dispatch('POST', '/parties/3/rsvps', ['employee_name' => 'Pam']);

        $this->assertSame('3:Pam', $response->body());
    }

    public function testItWrapsPlainStringsInAnHtmlResponse(): void
    {
        $router = new Router();
        $router->get('/', static fn (): string => 'judged');

        $response = $router->dispatch('GET', '/');

        $this->assertSame(200, $response->status());
        $this->assertSame('text/html; charset=utf-8', $response->header('Content-Type'));
    }

    public function testItTreatsRegexCharactersInPathsLiterally(): void
    {
        $router = new Router();
        $router->get('/offices/{slug}', static fn (): string => 'office');

        $this->assertNull($router->match('GET', '/offices'));
        $this->assertNotNull($router->match('GET', '/offices/utica'));
    }

    public function testNormaliseProducesACanonicalPath(): void
    {
        $this->assertSame('/', Router::normalise('/'));
        $this->assertSame('/', Router::normalise(''));
        $this->assertSame('/parties', Router::normalise('/parties/'));
        $this->assertSame('/parties/2', Router::normalise('/parties/2?x=1'));
    }
}

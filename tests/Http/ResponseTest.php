<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use PartyPlanningCommittee\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel tests the Response, with special attention to impish redirects.
 */
final class ResponseTest extends TestCase
{
    public function testItCarriesBodyStatusAndHeaders(): void
    {
        $response = new Response('coal', 418, ['X-Belsnickel' => 'watching']);

        $this->assertSame('coal', $response->body());
        $this->assertSame(418, $response->status());
        $this->assertSame('watching', $response->header('X-Belsnickel'));
        $this->assertNull($response->header('X-Santa'));
    }

    public function testHtmlResponsesDeclareTheirContentType(): void
    {
        $response = Response::html('<p>judged</p>');

        $this->assertSame(200, $response->status());
        $this->assertSame(['Content-Type' => 'text/html; charset=utf-8'], $response->headers());
    }

    public function testNotFoundUsesStatus404(): void
    {
        $this->assertSame(404, Response::notFound()->status());
    }

    public function testRedirectsPointAtInAppPaths(): void
    {
        $response = Response::redirect('/parties/7');

        $this->assertSame(302, $response->status());
        $this->assertSame('/parties/7', $response->header('Location'));
    }

    public function testRedirectsKeepQueryStrings(): void
    {
        $this->assertSame('/upcoming?office=utica', Response::redirect('/upcoming?office=utica')->header('Location'));
    }

    public function testItRefusesAnImpishOpenRedirect(): void
    {
        $this->assertSame('/evil', Response::redirect('https://impish.example.com/evil')->header('Location'));
        $this->assertSame('/', Response::redirect('//impish.example.com')->header('Location'));
    }
}

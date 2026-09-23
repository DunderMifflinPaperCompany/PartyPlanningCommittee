<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

/**
 * Belsnickel judges the Response: a status, honest headers, and a body. Nothing more.
 */
final class Response
{
    private int $status;
    /** @var array<string, string> */
    private array $headers;
    private string $body;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * @param array<string, string> $headers
     */
    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers + ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Admirable: redirects go only to in-app paths, so no impish hand may forge an open redirect.
     */
    public static function redirect(string $path, int $status = 302): self
    {
        $safePath = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '/', '/');
        $query = parse_url($path, PHP_URL_QUERY);

        if (is_string($query) && $query !== '') {
            $safePath .= '?' . $query;
        }

        return new self('', $status, ['Location' => $safePath]);
    }

    public static function notFound(string $body = 'Impish route! Belsnickel found nothing here.'): self
    {
        return self::html($body, 404);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Belsnickel sends the verdict to the browser. Only called by the front controller.
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $this->body;
    }
}

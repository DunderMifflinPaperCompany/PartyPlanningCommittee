<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

/**
 * Belsnickel routes the request. A route that matches by accident is impish, so every
 * pattern is anchored and every placeholder is spelled out.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => self::compile($pattern),
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /**
     * @return array{handler: callable, params: array<string, string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $path = self::normalise($path);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches) === 1) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                return ['handler' => $route['handler'], 'params' => $params];
            }
        }

        // Belsnickel is displeased: no route confessed to this path.
        return null;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function dispatch(string $method, string $path, array $input = []): Response
    {
        $match = $this->match($method, $path);

        if ($match === null) {
            return Response::notFound();
        }

        $result = ($match['handler'])($match['params'], $input);

        // Admirable: handlers may return a Response or a plain string, but nothing sneaks past typing.
        return $result instanceof Response ? $result : Response::html((string) $result);
    }

    public static function normalise(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function compile(string $pattern): string
    {
        $pattern = self::normalise($pattern);

        // Belsnickel quotes first so no impish character in a path may act as a metacharacter.
        $quoted = preg_quote($pattern, '#');

        $regex = preg_replace_callback(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $quoted
        );

        return '#^' . (string) $regex . '$#';
    }
}

<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

use InvalidArgumentException;
use Throwable;

/**
 * Belsnickel renders the templates and escapes every scrap of output. Unescaped
 * user input is the most impish sin of all, and it earns more than coal.
 */
final class View
{
    private string $templateDirectory;

    public function __construct(string $templateDirectory)
    {
        $this->templateDirectory = rtrim($templateDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Admirable: escaping lives in one place, so nobody may forget it twice.
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        // Belsnickel permits only tame template names. Traversal is impish beyond redemption.
        if (preg_match('#^[a-z0-9_]+(/[a-z0-9_]+)*$#', $template) !== 1) {
            throw new InvalidArgumentException('Impish template name! Belsnickel smells a path traversal.');
        }

        $path = $this->templateDirectory . '/' . $template . '.php';

        if (!is_file($path)) {
            throw new InvalidArgumentException('Impish template! Belsnickel cannot find ' . $template . '.');
        }

        return $this->capture($path, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderInLayout(string $template, array $data = [], string $layout = 'layout'): string
    {
        $content = $this->render($template, $data);

        return $this->render($layout, $data + ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function capture(string $path, array $data): string
    {
        $render = static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();

            try {
                include $__path;
            } catch (Throwable $e) {
                // Impish templates must not leave a half-written buffer behind.
                ob_end_clean();

                throw $e;
            }

            return (string) ob_get_clean();
        };

        return $render($path, $data);
    }
}

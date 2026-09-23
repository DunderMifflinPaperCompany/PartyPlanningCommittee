<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

/**
 * Belsnickel guards against forged requests. A form without a token is an impish
 * form, and Belsnickel turns it away at the door.
 */
final class CsrfGuard
{
    private const KEY = 'csrf_token';

    /** @var array<string, mixed> */
    private array $storage;

    /**
     * @param array<string, mixed> $storage
     */
    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    public function token(): string
    {
        if (!isset($this->storage[self::KEY]) || !is_string($this->storage[self::KEY]) || $this->storage[self::KEY] === '') {
            // Admirable: random_bytes, never rand(). Predictable tokens are no tokens at all.
            $this->storage[self::KEY] = bin2hex(random_bytes(32));
        }

        return (string) $this->storage[self::KEY];
    }

    /**
     * Belsnickel compares in constant time, so no impish timing trick may learn the token.
     */
    public function isValid(?string $candidate): bool
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        return hash_equals($this->token(), $candidate);
    }

    /**
     * @return array<string, mixed>
     */
    public function storage(): array
    {
        return $this->storage;
    }

    /**
     * Belsnickel binds the guard to the live session so the token survives a redirect.
     */
    public static function fromSession(): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $guard = new self($_SESSION);
        $_SESSION[self::KEY] = $guard->token();

        return $guard;
    }
}

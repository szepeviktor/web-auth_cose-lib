<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\EdDSA;

use Assert\Assertion;
use Cose\Algorithm\Signature\Signature;
use Cose\Algorithms;
use Cose\Key\Key;
use Cose\Key\OkpKey;
use InvalidArgumentException;
use function Safe\sodium_crypto_sign_verify_detached;
use function sodium_crypto_sign_detached;
use Throwable;

class EdDSA implements Signature
{
    public function sign(string $data, Key $key): string
    {
        $key = $this->handleKey($key);
        Assertion::true($key->isPrivate(), 'The key is not private');

        $x = $key->x();
        $d = $key->d();
        $secret = $d . $x;

        return match ($key->curve()) {
            OkpKey::CURVE_ED25519 => sodium_crypto_sign_detached($data, $secret),
            default => throw new InvalidArgumentException('Unsupported curve'),
        };
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        $key = $this->handleKey($key);
        if ($key->curve() !== OkpKey::CURVE_ED25519) {
            throw new InvalidArgumentException('Unsupported curve');
        }
        try {
            sodium_crypto_sign_verify_detached($signature, $data, $key->x());
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public static function identifier(): int
    {
        return Algorithms::COSE_ALGORITHM_EdDSA;
    }

    private function handleKey(Key $key): OkpKey
    {
        return OkpKey::create($key->getData());
    }
}

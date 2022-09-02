<?php

namespace app\Cache;

use Predis\Client;
use Predis\ClientContextInterface;
use function Sentry\captureException;

class RedisCache
{
    private Client $client;
    private array $localKeyValueStore;

    public function __construct()
    {
        $this->client = new Client();
        $this->localKeyValueStore = [];
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Generic get/set

    public function getString(string $key): ?string
    {
        $value = null;

        try {
            $value = $this->localKeyValueStore[$key]
                ?? $this->client->get($key);
        } catch (\Exception $ex) {
            captureException($ex);
        }

        if ($value === null)
            return null;

        return strval($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null, ?ClientContextInterface $transaction = null): bool
    {
        if ($transaction === null)
            $transaction = $this->client;

        try {
            if ($ttl !== null)
                $statusOrSelf = $transaction->setex(key: $key, seconds: $ttl, value: $value);
            else
                $statusOrSelf = $transaction->set(key: $key, value: $value);
            $result = !!$statusOrSelf;
        } catch (\Exception $ex) {
            captureException($ex);
            $result = false;
        }

        $this->localKeyValueStore[$key] = $value;
        return $result;
    }

    public function setMany(array $data, ?int $ttl = null): bool
    {
        $transaction = $this->client->transaction();

        foreach ($data as $key => $value) {
            $this->set($key, $value, $ttl, $transaction);
        }

        try {
            $transaction->execute();
            return true;
        } catch (\Exception $ex) {
            captureException($ex);
            return false;
        }
    }
}
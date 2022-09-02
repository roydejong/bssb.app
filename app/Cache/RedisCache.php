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

    public function set(string $key, mixed $value, ?int $ttlSeconds = null, ?ClientContextInterface $transaction = null): bool
    {
        if ($transaction === null)
            $transaction = $this->client;

        try {
            if ($ttlSeconds !== null)
                $statusOrSelf = $transaction->setex(key: $key, seconds: $ttlSeconds, value: $value);
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

    public function setMany(array $data, ?int $ttlSeconds = null): bool
    {
        try {
            $transaction = $this->client->transaction();

            foreach ($data as $key => $value) {
                $this->set($key, $value, $ttlSeconds, $transaction);
            }

            $transaction->execute();
            return true;
        } catch (\Exception $ex) {
            captureException($ex);
            return false;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Array get/set

    public function getArrayHash(string $key): ?array
    {
        if (isset($this->localKeyValueStore[$key]) && is_array($this->localKeyValueStore[$key]))
            return $this->localKeyValueStore[$key];

        try {
            $value = $this->client->hgetall($key);
            if (is_array($value) && !empty($value))
                return $value;
        } catch (\Exception $ex) {
            captureException($ex);
        }

        return null;
    }

    public function setArrayHash(string $key, array $dictionary, ?int $ttlSeconds = 0): bool
    {
        try {
            $transaction = $this->client->transaction();

            foreach ($dictionary as $subKey => $subValue)
                $transaction->hset($key, $subKey, $subValue);

            $this->localKeyValueStore[$key] = $dictionary;

            if ($ttlSeconds)
                $transaction->expire($key, $ttlSeconds);

            $transaction->execute();
            return true;
        } catch (\Exception $ex) {
            captureException($ex);
            return false;
        }
    }
}
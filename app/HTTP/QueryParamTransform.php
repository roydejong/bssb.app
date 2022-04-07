<?php

namespace app\HTTP;

class QueryParamTransform
{
    private string $baseUrl;
    private array $queryParams;

    public function __construct(string $baseUrl, array $queryParams)
    {
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;
    }

    public static function fromUrl(string $url): QueryParamTransform
    {
        $urlParts = explode('?', $url, 2);

        $baseUrl = $urlParts[0];
        $queryParams = [];

        if (isset($urlParts[1])) {
            parse_str($urlParts[1], $queryParams);
        }

        return new QueryParamTransform($baseUrl, $queryParams);
    }

    public static function fromRequest(Request $request): QueryParamTransform
    {
        return new QueryParamTransform($request->getUri(false), $request->queryParams);
    }

    public function set(string $key, mixed $value): QueryParamTransform
    {
        $this->queryParams[$key] = $value;
        return $this;
    }

    public function unset(string $key): QueryParamTransform
    {
        unset($this->queryParams[$key]);
        return $this;
    }

    public function toUrl(): string
    {
        $url = $this->baseUrl;

        if (!empty($this->queryParams)) {
            $url .= "?";
            $url .= http_build_query($this->queryParams);
        } else {
            $url = rtrim($url, "/");
        }

        return $url;
    }

    public function __toString(): string
    {
        return $this->toUrl();
    }
}
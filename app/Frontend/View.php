<?php

namespace app\Frontend;

use app\HTTP\Response;

class View
{
    protected string $fileName;
    protected array $context;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $this->context = [];
    }

    public function set(string $key, $value): void
    {
        $this->context[$key] = $value;
    }

    public function render(): string
    {
        return ViewRenderer::instance()
            ->render($this->fileName, $this->context);
    }

    public function asResponse(int $responseCode = 200): Response
    {
        return new Response($responseCode, $this->render(), "text/html");
    }
}
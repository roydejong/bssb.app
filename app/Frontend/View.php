<?php

namespace app\Frontend;

use app\HTTP\Response;

class View
{
    protected string $fileName;
    protected bool $liteMode;
    protected array $context;

    public function __construct(string $fileName, bool $liteMode = false)
    {
        $this->fileName = $fileName;
        $this->liteMode = $liteMode;
        $this->context = [];
    }

    public function set(string $key, $value): void
    {
        $this->context[$key] = $value;
    }

    public function render(): string
    {
        return ViewRenderer::instance()
            ->render($this->fileName, $this->context, !$this->liteMode);
    }

    public function asResponse(int $responseCode = 200): Response
    {
        return new Response($responseCode, $this->render(), "text/html");
    }
}
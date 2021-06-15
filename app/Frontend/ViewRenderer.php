<?php

namespace app\Frontend;

use app\Utils\TimeAgo;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class ViewRenderer
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    protected Environment $twig;

    private function __construct()
    {
        global $config;

        $this->twig = new Environment(new FilesystemLoader(DIR_VIEWS), [
            'cache' => $config['cache_enabled'] ? DIR_CACHE : false
        ]);

        $this->twig->addFilter(new TwigFilter('timeago', function ($input): string {
            try {
                $dt = new \DateTime($input);
                return TimeAgo::format($dt);
            } catch (\Exception) {
                return "unknown";
            }
        }));

        $this->twig->addFilter(new TwigFilter('timeago_html', function ($input): string {
            try {
                $dt = new \DateTime($input);
                $timeAgo = TimeAgo::format($dt);
                return "<abbr title='{$input}'>{$timeAgo}</abbr>";
            } catch (\Exception) {
                return "unknown";
            }
        }));
    }

    private function processContext(?array $userContext): array
    {
        if ($userContext === null) {
            $userContext = [];
        }

        // Add version (for cache busting)
        $versionFilePath = DIR_BASE . "/.version";
        $userContext['version_hash'] = trim(@file_get_contents($versionFilePath));
        $userContext['version_hash_short'] = substr($userContext['version_hash'],0,7);
        $userContext['version_date'] = @filemtime($versionFilePath);

        return $userContext;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Render

    public function render(string $viewFileName, ?array $context): string
    {
        return $this->twig->render($viewFileName, $this->processContext($context));
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Singleton

    private static ViewRenderer $viewRenderer;

    public static function instance(): ViewRenderer
    {
        if (!isset(self::$viewRenderer)) {
            self::$viewRenderer = new ViewRenderer();
        }

        return self::$viewRenderer;
    }
}
<?php

namespace app\Frontend;

use app\Session\Session;
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
        global $bssbConfig;

        $this->twig = new Environment(new FilesystemLoader(DIR_VIEWS), [
            'cache' => $bssbConfig['cache_enabled'] ? DIR_CACHE : false
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

        // Add version info (for cache busting)
        $versionFilePath = DIR_BASE . "/.version";
        $userContext['version_hash'] = trim(@file_get_contents($versionFilePath));
        $userContext['version_hash_short'] = substr($userContext['version_hash'],0,7);
        $userContext['version_date'] = @filemtime($versionFilePath);

        // Add session info
        $session = Session::getInstance();

        if ($session->getIsSteamAuthed()) {
            $player = $session->getPlayer();

            $userContext['steam_authed'] = true;
            $userContext['self_player_name'] = $player?->userName ?? "Steam User";
            $userContext['self_face_render'] = $player?->renderFaceHtml();
        }


        return $userContext;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Render

    public function render(string $viewFileName, ?array $context, bool $processContext = true): string
    {
        $contextArg = $processContext ? $this->processContext($context) : $context;
        return $this->twig->render($viewFileName, $contextArg);
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
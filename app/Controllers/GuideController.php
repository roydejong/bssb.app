<?php

namespace app\Controllers;

use app\Common\CVersion;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Session\Session;

class GuideController
{
    private const LastUpdate = "2022-03-02 21:30";

    private const VersionLatest = "1.20.0";
    private const VersionOverallMax = "1.18.3";
    private const VersionOverallMin = "1.17.1";
    private const PcSupportedVersion = "1.18.3";
    private const BmbfSupportedVersion = "1.17.1";

    private const PlatformSteamPC = "steam";
    private const PlatformOculusPC = "oculus-pc";
    private const PlatformQuest = "quest";
    private static $platformOptions = [
        self::PlatformSteamPC => "Steam (PC)",
        self::PlatformOculusPC => "Oculus (PC / Link)",
        self::PlatformQuest => "Oculus Quest"
    ];

    private static $allGameVersions = ["1.17.1", "1.18.0", "1.18.1",
        "1.18.2", "1.18.3", "1.19.0", "1.19.1", "1.20.0"];

    public function getGuideIndex(Request $request): Response
    {
        $session = Session::getInstance();

        $currentGameVersion = new CVersion(self::VersionLatest);

        $pageUrl = "/guide";

        if ($request->method === "POST") {
            $platform = $request->postParams['platform'];
            $version = $request->postParams['version'];

            $pageUrl = "/guide/{$platform}/{$version}";

            if (isset(self::$platformOptions[$platform]) &&
                (in_array($version, self::$allGameVersions) || $version === "other")) {
                return new RedirectResponse($pageUrl, 303);
            } else {
                return new Response(400);
            }
        }

        $view = new View('pages/guide.twig');
        $view->set('pageUrl', $pageUrl);
        $view->set('pageTitle', "Multiplayer Modding Guide");
        $view->set('pageDescr', "An interactive guide that will help you play custom songs in Beat Saber multiplayer.");
        $view->set('lastUpdate', self::LastUpdate);
        $view->set('currentGameVersion', $currentGameVersion);
        $view->set('allGameVersions', array_reverse(self::$allGameVersions));
        $view->set('platformOptions', self::$platformOptions);
        $view->set('platformValue', $session->get('guide_platform'));
        $view->set('versionValue', $session->get('guide_version'));
        return $view->asResponse();
    }

    public function getGuideResult(Request $request, string $platform, string $version): Response
    {
        if (!isset(self::$platformOptions[$platform]))
            // Invalid platform selection, go back to guide
            return new RedirectResponse('/guide');

        if ($version === "latest")
            // Redirect to latest version
            return new RedirectResponse("/guide/{$platform}/" . self::VersionLatest);

        if (!in_array($version, self::$allGameVersions) && $version !== "other")
            // Invalid version, go to "other" version
            return new RedirectResponse("/guide/{$platform}/other");

        if ($version !== "other")
            $version = new CVersion($version);

        $platformName = self::$platformOptions[$platform];
        $versionText = $version;

        $session = Session::getInstance();
        $session->set('guide_platform', $platform);
        $session->set('guide_version', $version);

        // -------------------------------------------------------------------------------------------------------------
        // Analysis

        $isPcVr = $platform === self::PlatformOculusPC || $platform === self::PlatformSteamPC;

        if ($isPcVr)
            $platformVersion = new CVersion(self::PcSupportedVersion);
        else
            $platformVersion = new CVersion(self::BmbfSupportedVersion);

        $absoluteMaxVersion = new CVersion(self::VersionOverallMax);
        $absoluteMinVersion = new CVersion(self::VersionOverallMin);
        $relMaxVersion = CVersion::min($platformVersion, $absoluteMaxVersion);

        $resultText = "";
        $resultInstruction = "";
        $resultBad = false;
        $resultEyes = null;
        $faqSets = [];

        if ($version === "other" || $version->lessThan($absoluteMinVersion)) {
            if ($version === "other") {
                $versionText = "Other/Older Version";
                $resultText = "The game version you're using is no longer, or not yet, supported by multiplayer mods.";
                $resultInstruction = "You'll have to upgrade or downgrade Beat Saber if you want to use multiplayer mods. For {$platformName}, switch to {$platformVersion}.";
            } else {
                $resultText = "The game version you're using is no longer supported by multiplayer mods.";
                $resultInstruction = "You'll have to upgrade Beat Saber if you want to use multiplayer mods. For {$platformName}, upgrade to {$platformVersion}.";
            }

            $resultBad = true;
            $resultEyes = "Eyes2";

            $faqSets[] = "versions";
            if ($version === "other") {
                $faqSets[] = "mpDowngrade";
                $faqSets[] = "downgrade";
            }
        } else if ($platform === self::PlatformQuest && $version->greaterThan($platformVersion)) {
            $resultText = "Sorry, you can't install mods for Beat Saber {$version} yet on Quest. You'll have to wait for BMBF and core mods to be updated first.";
            $resultInstruction = "If you want to use multiplayer mods, you should downgrade your game to Beat Saber {$platformVersion} for now.";
            $resultBad = true;
            $resultEyes = "Eyes10";

            $faqSets[] = "versions";
            $faqSets[] = "bmbfDowngrade";
            $faqSets[] = "downgrade";
        } else if ($version->greaterThan($absoluteMaxVersion)) {
            $resultText = "Sorry, that version is too new. It's not supported yet. There are no multiplayer mods for Beat Saber {$version} on any platform.";
            $resultInstruction = "If you want to use multiplayer mods on {$platformName}, you should downgrade your game to Beat Saber {$platformVersion}.";
            $resultBad = true;
            $resultEyes = "Eyes10";

            $faqSets[] = "versions";
            $faqSets[] = "downgrade";
        } else {
            $resultText = "You can use multiplayer mods on this game version.";
            $resultInstruction = "You'll need to install BeatTogether and MultiplayerCore. Check out the instructions below.";
            $resultBad = false;

            $faqSets[] = "install";
        }

        // -------------------------------------------------------------------------------------------------------------
        // Render

        $view = new View('pages/guide-result.twig');
        $view->set('pageTitle', "{$platformName} {$version} - Multiplayer Modding Guide");
        $view->set('pageDescr', "How to use mods in Beat Saber {$version} multiplayer on {$platformName}.");
        $view->set('lastUpdate', self::LastUpdate);
        $view->set('platformKey', $platform);
        $view->set('platformName', $platformName);
        $view->set('version', $version);
        $view->set('versionText', $versionText);
        $view->set('platformVersion', $platformVersion);
        $view->set('absMinVersion', $absoluteMinVersion);
        $view->set('absMaxVersion', $absoluteMaxVersion);
        $view->set('relMaxVersion', $relMaxVersion);
        $view->set('eyes', $resultEyes);
        $view->set('resultText', $resultText);
        $view->set('resultInstruction', $resultInstruction);
        $view->set('resultBad', $resultBad);
        $view->set('noIndex', ($version === "other"));
        $view->set('faqSet', $faqSets);
        return $view->asResponse();
    }
}
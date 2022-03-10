<?php

namespace app\Controllers\API\V1;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\BeatSaber\Enums\PlayerScoreRank;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\LevelHistory;
use app\Models\LevelHistoryPlayer;
use app\Models\Player;

class AnnounceResultsController
{
    public function announceResults(Request $request): Response
    {
        global $bssbConfig;

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $isClientRequest = $request->getIsValidModClientRequest();
        $isDediRequest = $request->getIsValidBeatDediRequest();

        if ((!$isClientRequest && !$isDediRequest)
            || !$request->getIsJsonRequest()
            || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        $modClientInfo = $request->getModClientInfo();
        $input = $request->getJson();

        $sessionGameId = $input['SessionGameId'] ?? null;
        $results = $input['Results'] ?? null;

        if (empty($sessionGameId) || empty($results)) {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Lookup

        /**
         * @var $levelHistory LevelHistory|null
         */
        $levelHistory = LevelHistory::query()
            ->where('session_game_id = ?', $sessionGameId)
            ->querySingleModel();

        if (empty($levelHistory)) {
            return new NotFoundResponse();
        }

        if ($levelHistory->endedAt && !($bssbConfig['allow_multiple_results'] ?? false)) {
            return new Response(200, "lmao ignored 👎");
        }

        // -------------------------------------------------------------------------------------------------------------
        // Process

        $levelHistory->endedAt = new \DateTime('now');
        $levelHistory->save();

        /**
         * @var $historyPlayers LevelHistoryPlayer[]
         */
        $historyPlayers = LevelHistoryPlayer::query()
            ->where('level_history_id = ?', $levelHistory->id)
            ->queryAllModelsIndexed('playerId');

        if (!empty($historyPlayers) || !is_array($results)) {
            $playerIds = array_keys($historyPlayers);

            /**
             * @var $playerProfiles Player[]
             */
            $playerProfiles = Player::query()
                ->where('id IN (?)', $playerIds)
                ->queryAllModelsIndexed('id');

            $userIdToPlayerId = [];

            foreach ($playerProfiles as $playerProfile) {
                $userIdToPlayerId[$playerProfile->userId] = $playerProfile->id;
            }

            $rankedHistoryPlayers = [];
            $finishedPlayerCount = 0;

            // Fill history players from the submitted results
            foreach ($results as $resultItem) {
                $userId = $resultItem['UserId'] ?? null;

                if (empty($userId) || !isset($userIdToPlayerId[$userId]))
                    continue;

                $playerId = $userIdToPlayerId[$userId];
                $playerProfile = $playerProfiles[$playerId];
                $historyPlayer = $historyPlayers[$playerId];

                $historyPlayer->endReason = isset($resultItem['LevelEndReason']) ? PlayerLevelEndReason::tryFrom(intval($resultItem['LevelEndReason'])) : null;
                $historyPlayer->endState = isset($resultItem['LevelEndState']) ? PlayerLevelEndState::tryFrom(intval($resultItem['LevelEndState'])) : null;
                $historyPlayer->rawScore = intval($resultItem['RawScore'] ?? 0);
                $historyPlayer->modifiedScore = intval($resultItem['ModifiedScore'] ?? 0);
                $historyPlayer->scoreRank = isset($resultItem['Rank']) ? PlayerScoreRank::tryFrom(intval($resultItem['Rank'])) : null;
                $historyPlayer->goodCuts = intval($resultItem['GoodCuts'] ?? 0);
                $historyPlayer->badCuts = intval($resultItem['BadCuts'] ?? 0);
                $historyPlayer->missCount = intval($resultItem['MissCount'] ?? 0);
                $historyPlayer->fullCombo = intval($resultItem['FullCombo'] ?? 0) === 1;
                $historyPlayer->maxCombo = intval($resultItem['MaxCombo'] ?? 0);

                $badgeData = $resultItem['Badge'] ?? null;
                if ($badgeData && is_array($badgeData)) {
                    $historyPlayer->badgeKey = $badgeData['Key'] ?? null;
                    $historyPlayer->badgeTitle = $badgeData['Title'] ?? null;
                    $historyPlayer->badgeSubtitle = $badgeData['Subtitle'] ?? null;
                }

                $rankedHistoryPlayers[$historyPlayer->modifiedScore] = $historyPlayer;
                $finishedPlayerCount++;
            }

            // Order by achieved score, assign ranks, save final history players
            krsort($rankedHistoryPlayers);
            $placement = 1;
            foreach ($rankedHistoryPlayers as $player) {
                if ($player->endState === PlayerLevelEndState::SongFinished) {
                    $player->placement = $placement;
                    $placement++;
                } else {
                    $player->placement = null; // did not finish level, do not rank
                }
                $player->save();
            }

            // Set finished player count on game record
            $levelHistory->finishedPlayerCount = $finishedPlayerCount;
            $levelHistory->save();
        }

        return new Response(200, "👍 very nice");
    }
}
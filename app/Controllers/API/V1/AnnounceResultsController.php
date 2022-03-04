<?php

namespace app\Controllers\API\V1;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
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

        if ($levelHistory->endedAt) {
            return new Response(200, "lmao ignored you but you still get a 200 👎");
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

            foreach ($results as $resultItem) {
                $userId = $resultItem['UserId'] ?? null;

                if (empty($userId) || !isset($userIdToPlayerId[$userId]))
                    continue;

                $playerId = $userIdToPlayerId[$userId];
                $playerProfile = $playerProfiles[$playerId];
                $historyPlayer = $historyPlayers[$playerId];

                $historyPlayer->endReason = isset($resultItem['LevelEndReason']) ? PlayerLevelEndReason::tryFrom($resultItem['LevelEndReason']) : null;
                $historyPlayer->endState = isset($resultItem['LevelEndState']) ? PlayerLevelEndState::tryFrom($resultItem['LevelEndState']) : null;
                $historyPlayer->rawScore = intval($resultItem['RawScore'] ?? 0);
                $historyPlayer->modifiedScore = intval($resultItem['ModifiedScore'] ?? 0);
                $historyPlayer->rank = intval($resultItem['Rank'] ?? 0);
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

                $historyPlayer->save();
            }
        }

        return new Response(200, "👍 very nice");
    }
}
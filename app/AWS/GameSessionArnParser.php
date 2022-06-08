<?php

namespace app\AWS;

class GameSessionArnParser
{
    public static function tryParse(string $arn): ?GameSessionArn
    {
        /**
         * @see https://docs.aws.amazon.com/general/latest/gr/aws-arns-and-namespaces.html
         *
         * General formats:
         *  - arn:partition:service:region:account-id:resource-id
         *  - arn:partition:service:region:account-id:resource-type/resource-id
         *  - arn:partition:service:region:account-id:resource-type:resource-id
         *
         * Be aware that the ARNs for some resources omit the Region, the account ID, or both the Region and the
         * account ID.
         *
         * Beat Saber game session sample:
         *  - arn:aws:gamelift:us-west-2::gamesession/fleet-2e923221-53b7-4e1c-86e8-c4d1d4684864/eu-central-1/1ce08ada6372ec4bb443c85042ddd4a4
         */

        // -------------------------------------------------------------------------------------------------------------
        // Parse base ARN

        $arnParts = explode(':', $arn);
        $i = 0;

        // Each ARN starts with an "arn:" prefix
        $arnPrefix = $arnParts[$i++] ?? null;

        if ($arnPrefix !== "arn")
            // Not an ARN
            return null;

        $result = new GameSessionArn();
        $result->awsPartition = $arnParts[$i++] ?? null;
        $result->awsService = $arnParts[$i++] ?? null;
        $result->awsRegion = $arnParts[$i++] ?? null;
        $result->awsAccountId = $arnParts[$i++] ?? null;
        $result->awsResourceId = $arnParts[$i++] ?? null;

        if ($result->awsService !== "gamelift")
            // Not a GameLift ARN
            return null;

        if (!$result->awsResourceId)
            // Need resource id
            return null;

        // -------------------------------------------------------------------------------------------------------------
        // Parse gamesession resource

        $resourceIdParts = explode('/', $result->awsResourceId);
        $i = 0;

        $gameSessionPrefix = $resourceIdParts[$i++] ?? null;

        if ($gameSessionPrefix !== "gamesession")
            // Not a gamesession resource
            return null;

        $result->fleetId = $resourceIdParts[$i++] ?? null;

        // Fleet region isn't always included for some reason; assume this means it's in the same region as AWS base
        if (count($resourceIdParts) > $i + 1) {
            $result->fleetRegion = $resourceIdParts[$i++] ?? null;
        } else {
            $result->fleetRegion = $result->awsRegion;
        }

        $result->gameSessionId = $resourceIdParts[$i++] ?? null;

        return $result;
    }
}
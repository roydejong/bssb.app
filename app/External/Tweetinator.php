<?php

namespace app\External;

use Abraham\TwitterOAuth\TwitterOAuth;
use app\BSSB;
use app\Cache\RedisCache;
use app\HTTP\Request;
use app\Models\SystemConfig;
use function Sentry\captureException;

class Tweetinator
{
    private SystemConfig $systemConfig;
    private TwitterOAuth $connection;
    private RedisCache $redis;

    public function __construct()
    {
        $this->systemConfig = BSSB::getSystemConfig();

        $this->connection = new TwitterOAuth
        (
            consumerKey: BSSB::getConfig('twitter_api_key'),
            consumerSecret: BSSB::getConfig('twitter_api_key_secret')
        );
        $this->connection->setApiVersion('2');

        $this->redis = BSSB::getRedis();
    }

    public function generateAuthorizeUrl(): ?string
    {
        try {
            $oauthTokens = $this->connection->oauth('oauth/request_token', [
                'oauth_callback' => "https://bssb.app/callback/twitter"
            ]);
        } catch (\Exception $ex) {
            captureException($ex);
            return null;
        }

        $oauthToken = $oauthTokens['oauth_token'] ?? null;
        $oauthTokenSecret = $oauthTokens['oauth_token_secret'] ?? null;

        if (!$oauthToken || !$oauthTokenSecret)
            return null;

        $this->redis->setArrayHash('twitter_oauth', $oauthTokens);

        return $this->connection->url('oauth/authorize', [
            'oauth_token' => $oauthToken
        ]);
    }

    public function handleOauthCallback(Request $request): bool
    {
        $sessionTokens = $this->redis->getArrayHash('twitter_oauth');

        $oauthToken = $sessionTokens['oauth_token'] ?? null;
        $oauthTokenSecret = $sessionTokens['oauth_token_secret'] ?? null;

        if (!$oauthToken || !$oauthTokenSecret)
            return false;

        $queryOauthToken = $request->queryParams['oauth_token'] ?? null;
        $queryOauthVerifier = $request->queryParams['oauth_verifier'] ?? null;

        if (!$queryOauthToken || $queryOauthToken !== $oauthToken || !$queryOauthVerifier)
            return false;

        $this->connection->setOauthToken($oauthToken, $oauthTokenSecret);

        try {
            $accessTokens = $this->connection->oauth("oauth/access_token", [
                'oauth_verifier' => $queryOauthVerifier
            ]);
        } catch (\Exception $ex) {
            captureException($ex);
            return false;
        }

        $oauthTokenFinal = $accessTokens['oauth_token'] ?? null;
        $oauthTokenSecretFinal = $accessTokens['oauth_token_secret'] ?? null;
        $userIdAuthed = $accessTokens['user_id'] ?? null;

        if (!$oauthTokenFinal || !$oauthTokenSecretFinal)
            return false;

        $this->systemConfig->twitterOauthToken = $oauthTokenFinal;
        $this->systemConfig->twitterOauthTokenSecret = $oauthTokenSecretFinal;
        $this->systemConfig->twitterUserId = $userIdAuthed;
        $this->systemConfig->save();
        return true;
    }

    /**
     * Tries to post a status update (Tweet), returning the Tweet ID if successful.
     */
    public function postTweet(string $tweetText): ?string
    {
        if (!$this->systemConfig->twitterOauthToken || !$this->systemConfig->twitterOauthTokenSecret)
            return null;

        $this->connection->setOauthToken(
            $this->systemConfig->twitterOauthToken,
            $this->systemConfig->twitterOauthTokenSecret
        );

        try {
            $response = $this->connection->post("tweets", [
                "text" => $tweetText,
            ], json: true);
            return $response?->data?->id ?? null;
        } catch (\Exception $ex) {
            captureException($ex);
            return null;
        }
    }
}
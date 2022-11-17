<?php

namespace app\Models;

use app\Utils\TextUtils;
use SoftwarePunt\Instarecord\Model;

class Changelog extends Model
{
    public int $id;
    public \DateTime $publishDate;
    public string $title;
    public ?string $text;
    public bool $isAlert;
    public ?string $tweetId;
    public bool $isHidden;

    public function getUrl(): string
    {
        $url = "https://twitter.com/BSSBapp";
        if ($this->tweetId)
            $url .= "/status/{$this->tweetId}";
        return $url;
    }

    public function getSiteDisplayTitle(): string
    {
        return self::preProcessSiteDisplayText($this->title);
    }

    public function getSiteDisplayText(): string
    {
        return self::preProcessSiteDisplayText($this->text);
    }

    public static function preProcessSiteDisplayText(string $str): string
    {
        // Remove whitelines
        $str = str_replace("\r", "", $str);
        $str = str_replace("\n", " ", $str);

        // Remove emoji
        $str = TextUtils::removeEmoji($str);

        return trim($str);
    }

    public function getTwitterText(): string
    {
        return trim($this->title . "\r\n" . $this->text);
    }
}
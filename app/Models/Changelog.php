<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class Changelog extends Model
{
    public int $id;
    public \DateTime $publishDate;
    public string $title;
    public ?string $text;
    public bool $isAlert;
    public ?string $tweetId;

    public function getUrl(): string
    {
        $url = "https://twitter.com/BSSBapp";
        if ($this->tweetId)
            $url .= "/status/{$this->tweetId}";
        return $url;
    }
}
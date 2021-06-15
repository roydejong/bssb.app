<?php

namespace Utils;

use app\Utils\TimeAgo;
use PHPUnit\Framework\TestCase;

class TimeAgoTest extends TestCase
{
    public function testFormat()
    {
        $now = new \DateTime('2001-02-03 04:05:06');

        $this->assertSame("now", TimeAgo::format(new \DateTime('2001-02-03 04:05:06'), $now));
        $this->assertSame("a few seconds ago", TimeAgo::format(new \DateTime('2001-02-03 04:05:00'), $now));
        $this->assertSame("in 30 seconds", TimeAgo::format(new \DateTime('2001-02-03 04:05:36'), $now));
        $this->assertSame("2 months ago", TimeAgo::format(new \DateTime('2000-12-03 04:05:06'), $now));
        $this->assertSame("1 year ago", TimeAgo::format(new \DateTime('2000-02-03 04:05:06'), $now));
        $this->assertSame("1 year, 1 month ago", TimeAgo::format(new \DateTime('2000-01-03 04:05:06'), $now));
    }
}

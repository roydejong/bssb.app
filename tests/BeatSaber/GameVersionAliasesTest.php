<?php

use app\BeatSaber\GameVersionAliases;
use app\Common\CVersion;
use PHPUnit\Framework\TestCase;

class GameVersionAliasesTest extends TestCase
{
    public function testGetAliasesFor()
    {
        $this->assertEmpty(
            GameVersionAliases::getAliasesFor(new CVersion("1.2.3"), false),
            "Should not return any versions for unknown / invalid versions (no includeBaseVersion)"
        );

        $this->assertEquals(
            [new CVersion("1.2.3")],
            GameVersionAliases::getAliasesFor(new CVersion("1.2.3"), true),
            "Should only return self versions for unknown / invalid versions (with includeBaseVersion)"
        );

        $this->assertEquals(
            [new CVersion("1.18.0"), new CVersion("1.18.1"), new CVersion("1.18.2")],
            GameVersionAliases::getAliasesFor(new CVersion("1.18.0"), true),
            "1.18.0 should return all (3) aliased versions for 1.18.0"
        );

        $this->assertEquals(
            [new CVersion("1.18.0"), new CVersion("1.18.1"), new CVersion("1.18.2")],
            GameVersionAliases::getAliasesFor(new CVersion("1.18.1"), true),
            "1.18.1 should return all (3) aliased versions for 1.18.0, sorted in order"
        );

        $this->assertEquals(
            [new CVersion("1.18.0"), new CVersion("1.18.1"), new CVersion("1.18.2")],
            GameVersionAliases::getAliasesFor(new CVersion("1.18.2"), true),
            "1.18.2 should return all (3) aliased version for 1.18.0, sorted in order"
        );
    }
}

<?php

use app\Models\HostedGame;
use app\Models\MasterServerInfo;
use PHPUnit\Framework\TestCase;

class MasterServerInfoTest extends TestCase
{
   public function testSync()
   {
       $now = new DateTime('now');
       $testDateTimeFirst = new DateTime('2022-01-01 12:34:56');
       $testDateTimeLast = new DateTime('2022-01-01 13:00:00');

       $testHost = "master.server.info.test";
       $testPort = 1234;
       $testStatusUrl = "https://{$testHost}/status";

       MasterServerInfo::query()
           ->where('host = ?', $testHost)
           ->limit(1)
           ->delete();

       // Initial creation
       $hostedGame = new HostedGame();
       $hostedGame->masterServerHost = $testHost;
       $hostedGame->masterServerPort = $testPort;
       $hostedGame->masterStatusUrl = $testStatusUrl;
       $hostedGame->firstSeen = $testDateTimeFirst;
       $hostedGame->lastUpdate = $testDateTimeLast;

       $syncedInfo = MasterServerInfo::syncFromGame($hostedGame);

       $this->assertNotNull($syncedInfo,
           "syncFromGame() should succeed and return record");
       $this->assertSame($testHost, $syncedInfo->host,
           "Master host should be synced");
       $this->assertSame($testPort, $syncedInfo->port,
           "Master port should be synced");
       $this->assertSame($testStatusUrl, $syncedInfo->statusUrl,
           "Master status URL should be synced");
       $this->assertEquals($testDateTimeFirst, $syncedInfo->firstSeen,
           "First seen should equal game first seen");
       $this->assertGreaterThanOrEqual($testDateTimeLast, $syncedInfo->lastSeen,
           "Last seen should equal game last seen");

       // Try to remove status URL
       $hostedGame->masterStatusUrl = null;

       $syncedInfo2 = MasterServerInfo::syncFromGame($hostedGame);

       $this->assertSame($syncedInfo->statusUrl, $syncedInfo2->statusUrl,
           "Status URL should remain unchanged if hosted game has none set");

       // Try to update status URL - to BeatTogether
       $hostedGame->masterStatusUrl = "https://master.beattogether.systems/status";

       $syncedInfo3 = MasterServerInfo::syncFromGame($hostedGame);

       $this->assertSame($syncedInfo->statusUrl, $syncedInfo3->statusUrl,
           "Status URL should remain unchanged if hosted game has it set to BeatTogether");

       // Try to update status URL - to new value
       $hostedGame->masterStatusUrl = "https://special.server/status";

       $syncedInfo3 = MasterServerInfo::syncFromGame($hostedGame);

       $this->assertSame($hostedGame->masterStatusUrl , $syncedInfo3->statusUrl,
           "Status URL should be updated if hosted game has it set to a custom value");
   }
}

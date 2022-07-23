<?php
declare(strict_types=1);

use app\Models\HostedGame;
use Phinx\Migration\AbstractMigration;

final class AddHostedGameUniqueIndex extends AbstractMigration
{
    public function change(): void
    {
        if ($this->isMigratingUp()) {
            echo "Cleaning up old duplicate records, preferring the most recent records..." . PHP_EOL;

            $recordsWithDupes = HostedGame::query()
                ->select('owner_id, host_secret, count(*) AS theCount')
                ->groupBy('owner_id, host_secret')
                ->having('theCount > 1')
                ->queryAllRows();

            $totalDels = 0;

            foreach ($recordsWithDupes as $dupeRow) {
                $ownerId = $dupeRow['owner_id'];
                $hostSecret = $dupeRow['host_secret'];

                $dupeIds = HostedGame::query()
                    ->select('id')
                    ->where('owner_id = ?', $ownerId)
                    ->andWhere('host_secret = ?', $hostSecret)
                    ->orderBy('last_update ASC, id ASC')
                    ->querySingleValueArray();

                if (count($dupeIds) > 1) {
                    array_pop($dupeIds); // keep most recent one

                    $delCount = HostedGame::query()
                        ->delete()
                        ->where('id IN (?)', $dupeIds)
                        ->execute();

                    echo " - Deleted {$delCount} duplicate(s) (ownerId={$ownerId}, hostSecret={$hostSecret})" . PHP_EOL;
                    $totalDels += $delCount;
                }
            }

            echo "Deleted {$totalDels} duplicate records in total" . PHP_EOL;
        }

        $this->table('hosted_games')
            ->addIndex(['owner_id', 'host_secret'], ['unique' => true])
            ->update();
    }
}

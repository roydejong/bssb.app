<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MarkExistingGamesAsStale extends AbstractMigration
{
    public function change(): void
    {
        // Mark all existing games as stale immediately to avoid returning every single game ever
        // Legitimately active games will send an update and be unmarked within a few minutes anyway
        $this->query("UPDATE hosted_games SET is_stale = 1;");
    }
}

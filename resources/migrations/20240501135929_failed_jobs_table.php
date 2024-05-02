<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FailedJobsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        if($this->hasTable('failed_jobs')) {
            $this->table('failed_jobs')->drop()->save();
            return;
        }
        
        $table = $this->table('failed_jobs', ['id' => false, 'primary_key' => 'uuid']);
        $table->addColumn('uuid', 'string', ['limit' => 36])
            ->addColumn('command', 'string')
            ->addColumn('exception', 'text')
            ->addColumn('failed_at', 'timestamp')
            ->create();
    }
}

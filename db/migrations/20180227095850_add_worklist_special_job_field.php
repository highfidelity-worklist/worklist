<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddWorklistSpecialJobField extends AbstractMigration
{
  public function up() {
    $this->table('worklist')
      ->addColumn('special_job', 'boolean', ['null' => false, 'default' => false])
      ->save();
  }

  public function down() {
    $this->table('worklist')
      ->removeColumn('special_job')
      ->save();
  }
}

<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddWorklistHmdRequiredField extends AbstractMigration
{
  public function up() {
    $this->table('worklist')
      ->addColumn('hmd_required', 'boolean', ['null' => false, 'default' => false])
      ->save();
  }

  public function down() {
    $this->table('worklist')
      ->removeColumn('special_job')
      ->save();
  }
}

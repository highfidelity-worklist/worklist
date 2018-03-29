<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddUsersHmdOwnerField extends AbstractMigration
{
  public function up() {
    $this->table('users')
      ->addColumn('hmd_owner', 'boolean', ['null' => false, 'default' => '0'])
      ->save();
  }

  public function down() {
    $this->table('users')
      ->removeColumn('hmd_owner')
      ->save();
  }
}

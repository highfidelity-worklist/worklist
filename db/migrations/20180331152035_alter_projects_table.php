<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AlterProjectsTable extends AbstractMigration
{
  public function up() {
    $this->table('projects')
      ->changeColumn('last_commit', 'datetime', ['null' => true])
      ->changeColumn('repository', 'string', ['null' => true, 'limit' => 255])
      ->removeColumn('require_sandbox')
      ->removeColumn('repo_type')
      ->save();
  }

  public function down() {
    $this->table('projects')
      ->changeColumn('last_commit', 'datetime', ['null' => false])
      ->changeColumn('repository', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('require_sandbox', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('repo_type', 'enum', ['null' => false, 'default' => 'svn', 'limit' => 3, 'values' => ['svn','git']])
      ->save();
  }
}

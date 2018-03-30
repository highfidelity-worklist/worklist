<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddReviewsTimestamps extends AbstractMigration
{
  public function up() {
    $this->table('reviews')
      ->addColumn('created', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('updated', 'datetime', ['null' => true])
      ->save();
  }

  public function down() {
    $this->table('reviews')
      ->removeColumn('created')
      ->removeColumn('updated')
      ->save();
  }
}

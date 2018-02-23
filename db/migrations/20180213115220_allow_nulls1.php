<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AllowNulls1 extends AbstractMigration
{
  public function up() {
    $this->table('fees')
      ->changeColumn('notes', 'text', ['null' => true, 'limit' => 65535])
      ->changeColumn('category', 'string', ['null' => true, 'limit' => 32])
      ->changeColumn('old_user_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('budgets')
      ->changeColumn('old_giver_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_receiver_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('comments')
      ->changeColumn('old_user_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('users')
      ->changeColumn('confirm', 'integer', ['null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('confirm_string', 'string', ['null' => false, 'limit' => 10])
      ->changeColumn('about', 'text', ['null' => true, 'limit' => 65535])
      ->changeColumn('contactway', 'string', ['null' => true, 'limit' => 255])
      ->changeColumn('payway', 'string', ['null' => true, 'limit' => 255])
      ->changeColumn('skills', 'string', ['null' => true, 'limit' => 255])
      ->changeColumn('timezone', 'string', ['null' => true, 'limit' => 10])
      ->save();

    $this->table('worklist')
      ->changeColumn('old_creator_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_mechanic_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_runner_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('bids')
      ->changeColumn('old_bidder_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->table('fees')
      ->changeColumn('notes', 'text', ['null' => false, 'limit' => 65535])
      ->changeColumn('category', 'string', ['null' => false, 'limit' => 32])
      ->changeColumn('old_user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('budgets')
      ->changeColumn('old_giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('comments')
      ->changeColumn('old_user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('users')
      ->changeColumn('confirm', 'integer', ['null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('confirm_string', 'string', ['null' => false, 'limit' => 10])
      ->changeColumn('about', 'text', ['null' => false, 'limit' => 65535])
      ->changeColumn('contactway', 'string', ['null' => false, 'limit' => 255])
      ->changeColumn('payway', 'string', ['null' => false, 'limit' => 255])
      ->changeColumn('skills', 'string', ['null' => false, 'limit' => 255])
      ->changeColumn('timezone', 'string', ['null' => false, 'limit' => 10])
      ->save();

    $this->table('worklist')
      ->changeColumn('old_creator_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_mechanic_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->changeColumn('old_runner_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

    $this->table('bids')
      ->changeColumn('old_bidder_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();

  }
}

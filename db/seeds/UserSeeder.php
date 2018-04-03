<?php

use Phinx\Seed\AbstractSeed;

include_once(dirname(__FILE__) . '/../../config.php');

class UserSeeder extends AbstractSeed
{
  public function run()
  {
    Core::bootstrap(false);
    $data = [];
    for ($i = 0; $i < 50; $i++) {
      $faker = Faker\Factory::create();
      $data[] = [
        'username' => $faker->email,
        'password' => '',
        'added' => date('Y-m-d H:i:s'),
        'nickname' => $faker->userName,
        'confirm' => 1,
        'confirm_string' => '',
        'country' => 'US',
        'city' => $faker->city,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName
      ];
    }
    $this->table('users')->insert($data)->save();
  }
}

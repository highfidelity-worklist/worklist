<?php

use Phinx\Seed\AbstractSeed;

include_once(dirname(__FILE__) . '/../../config.php');

class ProjectSeeder extends AbstractSeed
{
  public function run()
  {
    Core::bootstrap(false);
    $data = [];
    $users = array_map(function ($user) { return $user->getId(); }, User::getUserList());
    for ($i = 0; $i < 5; $i++) {
      $faker = Faker\Factory::create();
      $data[] = [
        'name' => ucfirst($faker->word) . ucfirst($faker->word),
        'description' => $faker->sentence(9),
        'budget' => $faker->randomFloat(2, 1500),
        'repository' => '',
        'contact_info' => $faker->email,
        'owner_id' => $faker->randomElement($users),
        'fund_id' => 0,
        'internal' => 1
      ];
    }
    $this->table('projects')->insert($data)->save();
  }
}

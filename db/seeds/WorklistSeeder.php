<?php

use Phinx\Seed\AbstractSeed;

include_once(dirname(__FILE__) . '/../../config.php');

class WorklistSeeder extends AbstractSeed
{
  public function run()
  {
    Core::bootstrap(false);
    $data = [];
    $users = array_map(function ($user) { return $user->getId(); }, User::getUserList());
    $project = new Project;
    $projects = array_map(function ($project) { return $project['project_id']; }, $project->getProjects());
    for ($i = 0; $i < 250; $i++) {
      $faker = Faker\Factory::create();
      $data[] = [
        'summary' => $faker->text(99),
        'creator_id' => $faker->randomElement($users),
        'mechanic_id' => 0,
        'runner_id' => 0,
        'status' => 'Suggestion',
        'notes' => $faker->text,
        'created' => $faker->dateTime('-5 days')->format('Y-m-d H:i:s'),
        'project_id' => $faker->randomElement($projects),
        'is_bug' => (int) $faker->boolean,
        'status_changed' => $faker->dateTimeBetween('-4 days', 'now')->format('Y-m-d H:i:s'),
        'hmd_required' => (int) $faker->boolean,
        'special_job' => (int) $faker->boolean
      ];
    }
    $this->table('worklist')->insert($data)->save();
  }
}

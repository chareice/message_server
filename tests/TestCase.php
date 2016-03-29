<?php
use Laravel\Lumen\Testing\DatabaseMigrations;

class TestCase extends Laravel\Lumen\Testing\TestCase
{
  use DatabaseMigrations;
  public static $databaseInitialized = false;

  public function setUp(){
    parent::setUp();
    $this->setFaker();
    $this->createApplication();
  }

  public function setFaker(){
    $this->faker = Faker\Factory::create();
    $this->faker->addProvider(new Faker\Provider\zh_CN\PhoneNumber($this->faker));
    $this->faker->addProvider(new Faker\Provider\zh_CN\Person($this->faker));
    $this->faker->addProvider(new Faker\Provider\zh_CN\Company($this->faker));
    $this->faker->addProvider(new Faker\Provider\zh_CN\Address($this->faker));
    $this->faker->addProvider(new Faker\Provider\Internet($this->faker));
  }
  public function prepareForTests(){
    self::$databaseInitialized = true;
  }

  public function tearDown(){
    parent::tearDown();
  }

  public function createApplication()
  {
    return require __DIR__.'/../bootstrap/app.php';
  }
}

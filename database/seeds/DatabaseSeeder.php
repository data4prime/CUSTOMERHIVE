<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        //$this->call(CBSeeder::class);
        //$this->command->info('Qlik settings...');
        //$this->call(Qlik_Sett::class);
        $this->call(Qlik_Conf::class);
    }
}

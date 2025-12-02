<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $letters = range('A', 'Z'); // auto generates A–Z

        foreach ($letters as $letter) {
            DB::table('groups')->insert([
                'name' => "Group".'-'.$letter,
            ]);
        }
    }
}

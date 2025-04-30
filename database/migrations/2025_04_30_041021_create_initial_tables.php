<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level');
            $table->timestamps();
        });

        Schema::create('lumps', function (Blueprint $table) {
            $table->id();
            $table->integer('wad_id');
            $table->string('name', 8);
            $table->integer('offset');
            $table->integer('size');
            $table->longText('data')->charset('binary')->nullable();
            $table->timestamps();
        });

        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->integer('lump_id');
            $table->integer('music_lump_id')->nullable();
            $table->integer('next_level_map_id')->nullable();
            $table->integer('next_secret_level_map_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('things', function (Blueprint $table) {
            $table->id();
            $table->integer('level_id')->nullable();
            $table->integer('thing_type_id');
            $table->integer('wad_id');
            $table->integer('angle');
            $table->integer('flags');
            $table->integer('type');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();
        });

        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->integer('spawn_health')->default(0);
            $table->integer('wad_id')->nullable();
            $table->string('category');
            $table->string('name');
            $table->timestamps();

            $table->unique(['type', 'wad_id']);
        });

        Schema::create('wads', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('name');
            $table->integer('comp_level_id')->nullable();
            $table->timestamps();
        });

        Schema::create('link_levels_things', function (Blueprint $table) {
            $table->id();
            $table->integer('level_id');
            $table->integer('thing_id');
            $table->boolean('flag_ambush')->default(false);
            $table->boolean('flag_coop')->default(false);
            $table->boolean('flag_deathmatch')->default(false);
            $table->boolean('flag_friendly')->default(false);
            $table->boolean('flag_multiplayer')->default(false);
            $table->boolean('flag_single')->default(false);
            $table->boolean('flag_skill1')->default(false);
            $table->boolean('flag_skill2')->default(false);
            $table->boolean('flag_skill3')->default(false);
            $table->timestamps();
        });

        DB::table('comp_levels')->insert([
            ['level' => -1,  'name' => 'Engine defaults (current version)'],
            ['level' => 0,   'name' => 'Doom v1.2'],
            ['level' => 1,   'name' => 'Doom v1.666'],
            ['level' => 2,   'name' => 'Doom v1.9'],
            ['level' => 3,   'name' => 'Ultimate Doom & Doom95'],
            ['level' => 4,   'name' => 'Final Doom'],
            ['level' => 5,   'name' => 'DOSDoom'],
            ['level' => 6,   'name' => 'TASDoom'],
            ['level' => 7,   'name' => "Boom's inaccurate vanilla compatibility mode"],
            ['level' => 8,   'name' => 'Boom v2.01'],
            ['level' => 9,   'name' => 'Boom v2.02'],
            ['level' => 10,  'name' => 'LxDoom'],
            ['level' => 11,  'name' => 'MBF'],
            ['level' => 12,  'name' => 'PrBoom v2.03beta'],
            ['level' => 13,  'name' => 'PrBoom v2.1.0'],
            ['level' => 14,  'name' => 'PrBoom v2.1.1 - 2.2.6'],
            ['level' => 15,  'name' => 'PrBoom v2.3.x'],
            ['level' => 16,  'name' => 'PrBoom v2.4.0'],
            ['level' => 17,  'name' => 'Engine defaults (current version)'],
            ['level' => 21,  'name' => 'MBF21'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('link_levels_things');
        Schema::dropIfExists('wads');
        Schema::dropIfExists('things');
        Schema::dropIfExists('thing_types');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('lumps');
        Schema::dropIfExists('comp_levels');
    }
};

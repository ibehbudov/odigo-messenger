<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();      // e.g. karina
            $table->string('display_name');
            $table->string('odigo_id');              // e.g. karina@odigo.im
            $table->unsignedTinyInteger('age');
            $table->string('gender');                // Male / Female
            $table->string('region');
            $table->string('language');
            $table->string('occupation');
            $table->string('topic');                 // Soccer, Music, ...
            $table->string('status')->default('Online'); // Online/Away/Busy/Invisible
            $table->string('mood')->default('Happy');
            $table->string('intention')->default('Chat');
            $table->string('zodiac');
            $table->string('sprite')->default('gold'); // sprite palette key
            $table->string('tagline')->nullable();
            $table->boolean('is_friend')->default(false);
            $table->timestamps();
        });

        Schema::create('odigo_messages', function (Blueprint $table) {
            $table->id();
            $table->string('peer');                  // the other party's handle
            $table->string('direction');             // out (me->peer) / in (peer->me)
            $table->string('type')->default('Message'); // Message/Chat request/URL/File
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odigo_messages');
        Schema::dropIfExists('people');
    }
};

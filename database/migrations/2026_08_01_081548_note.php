<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('note',function(Blueprint $table){
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('creater_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignID('organizationID')->nullable()->references('id')->on('organizations')->onDelete('cascade');
            $table->foreignId('replied_note_id')->nullable()->references('id')->on('note')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note');
    }
};

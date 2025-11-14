<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Change boolean columns to integer to handle SPK device values (like 255)
            $table->integer('status1')->nullable()->change();
            $table->integer('status2')->nullable()->change();
            $table->integer('status3')->nullable()->change();
            $table->integer('status4')->nullable()->change();
            $table->integer('status5')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('status1')->nullable()->change();
            $table->boolean('status2')->nullable()->change();
            $table->boolean('status3')->nullable()->change();
            $table->boolean('status4')->nullable()->change();
            $table->boolean('status5')->nullable()->change();
        });
    }
};
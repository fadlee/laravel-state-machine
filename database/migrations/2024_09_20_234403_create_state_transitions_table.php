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
        Schema::create('state_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('status_field')->default('status');
            $table->string('transition_name');
            $table->string('from_state');
            $table->string('to_state');

            $table->unique([
                'model_type',
                'status_field',
                'transition_name',
                'from_state',
                'to_state'
            ])->name('unique_state_transitions');

            $table->timestamps();
        });
        Schema::create('state_transition_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('state_transition_id')->nullable();
            $table->string('status_field');
            $table->string('transition_name');
            $table->string('from_state');
            $table->string('to_state');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_transition_logs');
        Schema::dropIfExists('state_transitions');
    }
};

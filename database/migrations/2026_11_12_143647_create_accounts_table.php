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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank');
            $table->string('details');
            $table->bigInteger('balance_in_cents');
            $table->bigInteger('min_balance_in_cents');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('currency_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};

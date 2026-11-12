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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount');
            $table->string('concept');
            $table->date('date');
            $table->foreignId('account_id')->constrained();
            $table->timestamps();
        });
    }
};

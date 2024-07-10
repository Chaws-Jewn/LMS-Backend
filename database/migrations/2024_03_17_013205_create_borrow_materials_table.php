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
        Schema::create('borrow_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->string('book_id');

            // Borrowing
            $table->timestamp('borrow_date')->nullable();
            $table->date('borrow_expiration')->nullable();
            $table->timestamp('date_returned')->nullable();

            // Reservations
            $table->timestamp('reserve_date')->nullable();
            $table->date('reserve_expiration')->nullable();
            $table->tinyInteger('reservation_type')->nullable();

            // payments
            $table->float('fine', 2);

            // 0 -> paid, 1 -> pending, 2 -> N/A
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            // indexes
            $table->index('status');
            $table->foreign('book_id')->references('accession')->on('materials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_materials');
    }
};

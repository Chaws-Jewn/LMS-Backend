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
        Schema::create('programs', function (Blueprint $table) {
            $table->string('program_short', 10)->unique();
            $table->string('program_full', 100);
            $table->string('department_short');
            $table->string('department_full');
            $table->string('category');
            $table->timestamps();

            $table->primary('program_short');
        });

        Schema::create('patrons', function (Blueprint $table) {
            $table->id();
            $table->string('patron')->unique();
            $table->string('description')->nullable();
            $table->decimal('fine', 8, 2)->nullable();
            $table->integer('hours_allowed')->nullable();
            $table->integer('materials_allowed')->nullable();
            $table->timestamps(1);
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('role')->default('user');
            $table->foreignId('patron_id')->nullable()->constrained('patrons');
            $table->string('password');
            $table->rememberToken();
        
            // details
            $table->string('first_name', 30);
            $table->string('middle_name', 30)->nullable();
            $table->string('last_name', 30);
            $table->integer('gender')->nullable();
            $table->string('ext_name', 10)->nullable();
            $table->string('program')->nullable();
            $table->string('position', 50)->nullable(); 
            $table->string('profile_image')->nullable();
            
            // New columns
            $table->string('main_address')->nullable();
            $table->string('domain_email')->nullable();
            
            $table->timestamps();
            $table->softDeletes();  

            $table->foreign('program')->references('program_short')->on('programs');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
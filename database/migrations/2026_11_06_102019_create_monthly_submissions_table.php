<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->string('status', 30)->default('draft');

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'year', 'month'],
                'unique_user_monthly_submission'
            );

            $table->index(['department_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_submissions');
    }
};

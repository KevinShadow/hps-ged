<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('monthly_submission_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('original_name');
            $table->string('stored_name');

            $table->string('disk', 50)->default('external');
            $table->string('path', 500);

            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');

            $table->timestamps();

            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

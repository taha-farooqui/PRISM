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
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->after('mode')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('flashcard_set_id')->nullable()->after('quiz_id')->constrained('flashcard_sets')->cascadeOnDelete();
            $table->foreignId('generated_video_id')->nullable()->after('flashcard_set_id')->constrained('generated_videos')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropForeign(['flashcard_set_id']);
            $table->dropForeign(['generated_video_id']);
            $table->dropColumn(['quiz_id', 'flashcard_set_id', 'generated_video_id']);
        });
    }
};

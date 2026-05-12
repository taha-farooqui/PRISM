<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->string('progress_phase', 50)->nullable()->after('status');
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('progress_phase');
        });
    }

    public function down(): void
    {
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->dropColumn(['progress_phase', 'progress_percent']);
        });
    }
};

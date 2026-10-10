<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name_bn');      // intro text on the category page (SEO)
            $table->text('description_bn')->nullable()->after('description');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->json('quick_info')->nullable()->after('feeding_guide'); // "এক নজরে" box: [[label, value], ...]
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn(['description', 'description_bn']));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('quick_info'));
    }
};

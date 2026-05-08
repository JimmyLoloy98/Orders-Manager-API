<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla dedicada a registrar los AUMENTOS de ítems en una orden.
     * Los AUMENTOS se insertan aquí (para impresión incremental).
     * Las REDUCCIONES afectan directamente la tabla order_items.
     */
    public function up(): void
    {
        Schema::create('order_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('dining_table_id')->constrained()->onDelete('cascade');
            $table->json('items'); // JSON de los ítems AÑADIDOS en este aumento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_additions');
    }
};

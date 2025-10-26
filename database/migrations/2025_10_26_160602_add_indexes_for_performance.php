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
        // Add indexes to transactions table for better performance
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('trx_id', 'idx_transactions_trx_id');
            $table->index('buyer_id', 'idx_transactions_buyer_id');
            $table->index('status', 'idx_transactions_status');
            $table->index('payment_status', 'idx_transactions_payment_status');
            $table->index('created_at', 'idx_transactions_created_at');
            $table->index(['buyer_id', 'status'], 'idx_transactions_buyer_status');
        });

        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            $table->index('brand_id', 'idx_products_brand_id');
            $table->index('product_status', 'idx_products_status');
            $table->index('buyer_sku_code', 'idx_products_sku_code');
        });

        // Add indexes to brands table
        Schema::table('brands', function (Blueprint $table) {
            $table->index('category_id', 'idx_brands_category_id');
            $table->index('brand_status', 'idx_brands_status');
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_trx_id');
            $table->dropIndex('idx_transactions_buyer_id');
            $table->dropIndex('idx_transactions_status');
            $table->dropIndex('idx_transactions_payment_status');
            $table->dropIndex('idx_transactions_created_at');
            $table->dropIndex('idx_transactions_buyer_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_brand_id');
            $table->dropIndex('idx_products_status');
            $table->dropIndex('idx_products_sku_code');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('idx_brands_category_id');
            $table->dropIndex('idx_brands_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role');
        });
    }
};

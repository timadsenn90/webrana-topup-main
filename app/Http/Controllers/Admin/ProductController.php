<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Product', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            
            $typeId = null;
            if (!empty($validated['type_name'])) {
                // Check if the type already exists
                $type = Type::firstOrCreate(
                    ['type_name' => $validated['type_name']],
                    ['type_id' => Str::uuid(), 'type_status' => true]
                );
                $typeId = $type->type_id;
            }

            Product::create([
                'product_name' => $validated['product_name'],
                'brand_id' => $validated['brand_id'],
                'type_id' => $typeId,
                'price' => $validated['price'],
                'seller_name' => $validated['seller_name'] ?? null,
                'selling_price' => $validated['selling_price'],
                'buyer_sku_code' => $validated['buyer_sku_code'],
                'desc' => $validated['desc'],
                'product_status' => $validated['product_status'],
                'buyer_product_status' => $validated['buyer_product_status'] ?? false,
                'seller_product_status' => $validated['seller_product_status'] ?? false,
                'unlimited_stock' => $validated['unlimited_stock'] ?? false,
                'stock' => $validated['stock'] ?? 0,
                'multi' => $validated['multi'] ?? false,
                'start_cut_off' => $validated['start_cut_off'] ?? null,
                'end_cut_off' => $validated['end_cut_off'] ?? null,
            ]);

            DB::commit();

            Log::info('Product created successfully', ['product' => $validated['product_name']]);

            return redirect()->back()->with('success', 'Produk berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal menambahkan produk: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validated();

            $product->update([
                'product_name' => $validated['product_name'],
                'price' => $validated['price'],
                'selling_price' => $validated['selling_price'],
                'product_status' => $validated['product_status'],
            ]);

            Log::info('Product updated successfully', ['product_id' => $id]);

            return redirect()->back()->with('flash', [
                'message' => 'Produk berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('flash', [
                'message' => 'Gagal mengupdate produk: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $product->product_name;
            $product->delete();

            Log::info('Product deleted successfully', [
                'product_id' => $id,
                'product_name' => $productName
            ]);

            return redirect()->back()->with('flash', [
                'success' => 'Produk berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete product', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('flash', [
                'error' => 'Gagal menghapus produk'
            ]);
        }
    }
}


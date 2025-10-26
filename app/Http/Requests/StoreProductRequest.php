<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'brand_id' => 'required|string|exists:brands,brand_id',
            'type_name' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gte:price',
            'buyer_sku_code' => 'required|string|max:255|unique:products,buyer_sku_code',
            'desc' => 'required|string|max:5000',
            'product_status' => 'required|boolean',
            'seller_name' => 'nullable|string|max:255',
            'buyer_product_status' => 'nullable|boolean',
            'seller_product_status' => 'nullable|boolean',
            'unlimited_stock' => 'nullable|boolean',
            'stock' => 'nullable|numeric|min:0',
            'multi' => 'nullable|boolean',
            'start_cut_off' => 'nullable|string|max:10',
            'end_cut_off' => 'nullable|string|max:10',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk harus diisi',
            'brand_id.required' => 'Brand harus dipilih',
            'brand_id.exists' => 'Brand tidak ditemukan',
            'price.required' => 'Harga modal harus diisi',
            'price.min' => 'Harga modal minimal Rp 0',
            'selling_price.required' => 'Harga jual harus diisi',
            'selling_price.gte' => 'Harga jual harus lebih besar atau sama dengan harga modal',
            'buyer_sku_code.required' => 'SKU Code harus diisi',
            'buyer_sku_code.unique' => 'SKU Code sudah digunakan',
            'desc.required' => 'Deskripsi produk harus diisi',
            'product_status.required' => 'Status produk harus dipilih',
        ];
    }

    /**
     * Sanitize input data
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'product_name' => strip_tags($this->product_name ?? ''),
            'type_name' => strip_tags($this->type_name ?? ''),
            'buyer_sku_code' => strtoupper(strip_tags($this->buyer_sku_code ?? '')),
            'seller_name' => strip_tags($this->seller_name ?? ''),
        ]);
    }
}

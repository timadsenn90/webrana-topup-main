<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gte:price',
            'product_status' => 'required|boolean',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk harus diisi',
            'price.required' => 'Harga modal harus diisi',
            'price.min' => 'Harga modal minimal Rp 0',
            'selling_price.required' => 'Harga jual harus diisi',
            'selling_price.gte' => 'Harga jual harus lebih besar atau sama dengan harga modal',
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
        ]);
    }
}

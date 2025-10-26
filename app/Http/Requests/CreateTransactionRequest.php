<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string|max:255',
            'server_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:1|max:100000000',
            'method_code' => 'nullable|string|max:50',
            'customer_name' => 'required|string|max:255',
            'email_customer' => 'required|email|max:255',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'product_code' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'product_brand' => 'required|string|max:255',
            'product_price' => 'required|numeric|min:1',
            'values' => 'nullable|array',
            'unique_code' => 'nullable|numeric|min:0|max:999',
            'bankID' => 'nullable|exists:bank_account_mootas,bank_id',
            'username' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID harus diisi',
            'amount.required' => 'Jumlah pembayaran harus diisi',
            'amount.min' => 'Jumlah pembayaran minimal Rp 1',
            'customer_name.required' => 'Nama pelanggan harus diisi',
            'email_customer.required' => 'Email harus diisi',
            'email_customer.email' => 'Format email tidak valid',
            'phone_number.required' => 'Nomor telepon harus diisi',
            'phone_number.regex' => 'Format nomor telepon tidak valid (10-15 digit)',
            'product_code.required' => 'Kode produk harus diisi',
            'product_name.required' => 'Nama produk harus diisi',
            'product_brand.required' => 'Brand produk harus diisi',
            'product_price.required' => 'Harga produk harus diisi',
            'product_price.min' => 'Harga produk minimal Rp 1',
        ];
    }

    /**
     * Sanitize input data
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'customer_name' => strip_tags($this->customer_name ?? ''),
            'email_customer' => filter_var($this->email_customer ?? '', FILTER_SANITIZE_EMAIL),
            'phone_number' => preg_replace('/[^0-9]/', '', $this->phone_number ?? ''),
            'username' => strip_tags($this->username ?? ''),
        ]);
    }
}

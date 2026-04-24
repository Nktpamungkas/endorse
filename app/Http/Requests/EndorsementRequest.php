<?php

namespace App\Http\Requests;

use App\Models\Endorsement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EndorsementRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand_name' => ['required', 'string', 'max:255'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', Rule::in(array_keys(Endorsement::PLATFORM_OPTIONS))],
            'content_type' => ['required', Rule::in(array_keys(Endorsement::CONTENT_TYPE_OPTIONS))],
            'status' => ['required', Rule::in(array_keys(Endorsement::STATUS_OPTIONS))],
            'deal_date' => ['nullable', 'date'],
            'product_ordered_at' => ['nullable', 'date'],
            'product_received_at' => ['nullable', 'date', 'after_or_equal:product_ordered_at'],
            'draft_deadline' => ['nullable', 'date'],
            'storyline_required' => ['sometimes', 'boolean'],
            'storyline_done' => ['sometimes', 'boolean'],
            'drive_uploaded' => ['sometimes', 'boolean'],
            'approved_at' => ['nullable', 'date'],
            'posting_date' => ['nullable', 'date'],
            'posted_at' => ['nullable', 'date'],
            'insight_due_at' => ['nullable', 'date'],
            'insight_sent_at' => ['nullable', 'date', 'after_or_equal:posted_at'],
            'boostcode_required' => ['sometimes', 'boolean'],
            'boostcode_duration_days' => [
                Rule::requiredIf($this->boolean('boostcode_required')),
                'nullable',
                'integer',
                'min:7',
                'max:365',
            ],
            'self_purchase' => ['sometimes', 'boolean'],
            'checkout_proof' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,webp',
                'max:4096',
            ],
            'financial_mode' => ['required', Rule::in(array_keys(Endorsement::FINANCIAL_MODE_OPTIONS))],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'reimburse_amount' => [
                Rule::requiredIf($this->input('financial_mode') === 'reimburse_duluan' && $this->boolean('self_purchase')),
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->input('financial_mode') === 'reimburse_duluan' && $this->boolean('self_purchase'), ['gt:0']),
            ],
            'product_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(array_keys(Endorsement::PAYMENT_STATUS_OPTIONS))],
            'payment_due_date' => ['nullable', 'date'],
            'payment_received_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'storyline_required' => $this->boolean('storyline_required'),
            'storyline_done' => $this->boolean('storyline_done'),
            'drive_uploaded' => $this->boolean('drive_uploaded'),
            'boostcode_required' => $this->boolean('boostcode_required'),
            'self_purchase' => $this->boolean('self_purchase'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brand_name.required' => 'Nama brand wajib diisi.',
            'platform.required' => 'Pilih platform campaign.',
            'content_type.required' => 'Pilih jenis konten yang akan dibuat.',
            'status.required' => 'Pilih status endorse saat ini.',
            'payment_status.required' => 'Pilih status payment.',
            'product_received_at.after_or_equal' => 'Tanggal produk diterima tidak boleh lebih awal dari tanggal order produk.',
            'insight_sent_at.after_or_equal' => 'Tanggal kirim insight tidak boleh lebih awal dari tanggal konten tayang.',
            'boostcode_duration_days.required' => 'Durasi boostcode wajib diisi saat boostcode dicentang.',
            'boostcode_duration_days.min' => 'Durasi boostcode minimal 7 hari.',
            'boostcode_duration_days.max' => 'Durasi boostcode maksimal 365 hari.',
            'checkout_proof.mimes' => 'Bukti checkout harus berupa JPG, PNG, WEBP, atau PDF.',
            'checkout_proof.max' => 'Ukuran bukti checkout maksimal 4 MB.',
            'reimburse_amount.required' => 'Nominal reimburse wajib diisi untuk skema Reimburse Duluan.',
            'reimburse_amount.gt' => 'Nominal reimburse untuk skema Reimburse Duluan harus lebih dari 0.',
        ];
    }
}

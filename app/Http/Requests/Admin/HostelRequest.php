<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HostelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_hostels') || $this->user()?->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hostelId = $this->route('hostel')?->id;

        return [
            'name' => 'required|string|max:255|unique:hostels,name,' . $hostelId,
            'gender_type' => 'required|in:male,female,mixed',
            'description' => 'nullable|string',
            'payment_gateway' => 'nullable|string|in:seerbit,squadco,paystack,none',
            'squadco_secret_key' => 'nullable|string|max:255',
            'squadco_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:255',
            'paystack_public_key' => 'nullable|string|max:255',
            'seerbit_secret_key' => 'nullable|string|max:255',
            'seerbit_public_key' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get sanitized data for Hostel creation or update.
     */
    public function sanitized(): array
    {
        $validated = $this->validated();

        if (($validated['payment_gateway'] ?? null) === 'none') {
            $validated['payment_gateway'] = null;
        }

        return $validated;
    }
}

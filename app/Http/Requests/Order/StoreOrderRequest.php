<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a new sales order created through the web (Inertia) surface.
 *
 * Rules are unchanged from the previous inline validation in
 * Order\OrderController::store; org-scoped existence checks read the
 * authenticated user's organization.
 */
final class StoreOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (blank($this->input('customer_name'))) {
            $this->merge([
                'customer_name' => 'Walk-in Customer',
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_tin' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
            'cash_tendered' => 'nullable|numeric|min:0',
            'change_due' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
            'currency' => 'nullable|string|max:3',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'order_date' => 'required|date',
            'shipping' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('organization_id', $organizationId)],
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('organization_id', $organizationId)],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('organization_id', $organizationId)],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}

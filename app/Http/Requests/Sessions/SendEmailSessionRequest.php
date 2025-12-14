<?php

namespace App\Http\Requests\Sessions;

use App\Models\Customer;
use App\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendEmailSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $session = $this->route('session');

        if (! $session instanceof Session) {
            return false;
        }

        return $session->user_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'redirect_to_customer' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerId = $this->input('customer_id');
            $session = $this->route('session');

            if (! $customerId || ! $session instanceof Session) {
                return;
            }

            if (! $session->relationLoaded('customers')) {
                $session->load('customers');
            }

            $customer = Customer::where('id', $customerId)
                ->where('user_id', Auth::id())
                ->where('is_active', true)
                ->first();

            if (! $customer) {
                $validator->errors()->add(
                    'customer_id',
                    'Le client sélectionné n\'existe pas ou n\'est pas actif.'
                );

                return;
            }

            if (! $session->customers->contains($customerId)) {
                $validator->errors()->add(
                    'customer_id',
                    'Ce client n\'est pas associé à cette séance.'
                );

                return;
            }

            if (! $customer->email) {
                $validator->errors()->add(
                    'customer_id',
                    'Ce client n\'a pas d\'adresse email.'
                );
            }
        });
    }
}

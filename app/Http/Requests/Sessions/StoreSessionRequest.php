<?php

namespace App\Http\Requests\Sessions;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class StoreSessionRequest extends BaseSessionRequest
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
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ], $this->customerRules(), $this->exerciseRules());
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
            $customerIds = $this->input('customer_ids', []);

            if (! empty($customerIds)) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $teamMemberIds = $user->teamMemberIds();
                $customers = Customer::whereIn('id', $customerIds)
                    ->whereIn('user_id', $teamMemberIds)
                    ->get();

                if ($customers->count() !== count($customerIds)) {
                    $validator->errors()->add(
                        'customer_ids',
                        'Un ou plusieurs clients ne sont pas accessibles.'
                    );
                }
            }
        });
    }
}

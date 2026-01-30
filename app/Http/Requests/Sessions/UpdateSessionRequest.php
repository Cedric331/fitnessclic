<?php

namespace App\Http\Requests\Sessions;

use App\Models\Customer;
use App\Models\Session;
use Illuminate\Support\Facades\Auth;

class UpdateSessionRequest extends BaseSessionRequest
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
        return array_merge([
            'name' => ['nullable', 'string', 'max:255'],
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

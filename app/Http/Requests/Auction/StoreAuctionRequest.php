<?php

namespace App\Http\Requests\Auction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionRequest extends FormRequest
{
    /**
     * Treat a blank password field as "no password" rather than failing the
     * minimum length rule.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4'],
            'budget_per_participant' => ['required', 'integer', 'min:1'],
            'slots_goalkeeper' => ['required', 'integer', 'min:1'],
            'slots_defender' => ['required', 'integer', 'min:1'],
            'slots_midfielder' => ['required', 'integer', 'min:1'],
            'slots_forward' => ['required', 'integer', 'min:1'],
            'participants' => ['required', 'array', 'min:2'],
            'participants.*.name' => ['required', 'string', 'max:255'],
            'participants.*.is_agent' => ['sometimes', 'boolean'],
        ];
    }
}

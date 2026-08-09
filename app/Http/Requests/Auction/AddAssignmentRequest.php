<?php

namespace App\Http\Requests\Auction;

use Illuminate\Contracts\Validation\ValidationRule;

class AddAssignmentRequest extends StoreAssignmentRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'player_id' => ['required', 'integer', 'exists:players,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Auction;

use App\Models\Auction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $auction = $this->route('auction');
        $auctionId = $auction instanceof Auction ? $auction->id : null;

        return [
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'called_by_auction_participant_id' => [
                'required',
                'integer',
                Rule::exists('auction_participants', 'id')->where('auction_id', $auctionId),
            ],
        ];
    }
}

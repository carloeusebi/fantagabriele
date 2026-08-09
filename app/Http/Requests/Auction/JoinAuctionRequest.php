<?php

namespace App\Http\Requests\Auction;

use App\Models\Auction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinAuctionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $auction = $this->route('auction');
        $auctionId = $auction instanceof Auction ? $auction->id : null;

        return [
            'auction_participant_id' => [
                'nullable',
                'integer',
                Rule::exists('auction_participants', 'id')->where('auction_id', $auctionId),
            ],
        ];
    }
}

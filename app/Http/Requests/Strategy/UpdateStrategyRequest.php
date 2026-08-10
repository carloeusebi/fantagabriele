<?php

namespace App\Http\Requests\Strategy;

use App\Enums\PlayerRole;
use App\Enums\StrategyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStrategyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(StrategyType::cases(), 'value'))],
            'budget_goalkeeper_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'budget_defender_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'budget_midfielder_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'budget_forward_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'locked_roles' => ['sometimes', 'array'],
            'locked_roles.*' => [Rule::in(array_column(PlayerRole::cases(), 'value'))],
            'comment' => ['nullable', 'string', 'max:2000'],
            'priority_player_ids' => ['sometimes', 'array'],
            'priority_player_ids.*' => ['integer', Rule::exists('players', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = (int) $this->input('budget_goalkeeper_percent', 0)
                + (int) $this->input('budget_defender_percent', 0)
                + (int) $this->input('budget_midfielder_percent', 0)
                + (int) $this->input('budget_forward_percent', 0);

            if ($total !== 100) {
                $validator->errors()->add('budget_goalkeeper_percent', 'La somma delle percentuali deve essere 100.');
            }
        });
    }
}

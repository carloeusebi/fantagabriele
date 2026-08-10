import type { PlayerRoleValue } from '@/types/auction';

export type StrategyTypeValue = 'diversified' | 'balanced' | 'superstars';

export type StrategyTypeOption = {
    value: StrategyTypeValue;
    label: string;
    description: string;
};

export type Strategy = {
    id: number;
    user_id: number;
    name: string;
    type: StrategyTypeValue;
    budget_goalkeeper_percent: number;
    budget_defender_percent: number;
    budget_midfielder_percent: number;
    budget_forward_percent: number;
    locked_roles: PlayerRoleValue[];
    comment: string | null;
    priority_player_ids: number[];
};

export type StrategyListItem = {
    id: number;
    name: string;
    type: StrategyTypeValue;
    type_label: string;
};

export type StrategyBrief = {
    id: number;
    name: string;
    type: StrategyTypeValue;
    type_label: string;
    budget_plan: Record<PlayerRoleValue, number>;
    priority_players: Array<{
        id: number;
        name: string;
        role: PlayerRoleValue;
        team: string | null;
    }>;
    comment: string | null;
};

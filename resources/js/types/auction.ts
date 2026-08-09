export type AuctionStatus = 'setup' | 'in_progress' | 'paused' | 'completed';

export type PlayerRoleValue = 'P' | 'D' | 'C' | 'A';

export type RoleOption = {
    value: PlayerRoleValue;
    label: string;
};

export type Team = {
    id: number;
    name: string;
};

export type Player = {
    id: number;
    name: string;
    role: PlayerRoleValue;
    current_quotation: number;
    fvm: number | null;
    team: Team | null;
};

export type RosterSlot = {
    role: PlayerRoleValue;
    label: string;
    filled: number;
    total: number;
};

export type RosterEntry = {
    assignment_id: number;
    price: number;
    player_id: number;
    player_name: string;
    role: PlayerRoleValue;
    team: string | null;
};

export type AuctionParticipant = {
    id: number;
    name: string;
    is_agent: boolean;
    call_order: number;
    claimed_by: string | null;
    claimed_by_user_id: number | null;
    budget_remaining: number;
    slots: RosterSlot[];
    roster: RosterEntry[];
};

export type AuctionViewer = {
    auction_participant_id: number | null;
};

export type AuctionPermissions = {
    manage: boolean;
    updateStatus: boolean;
    call: boolean;
    resolveCall: boolean;
    advise: boolean;
};

export type AuctionCall = {
    id: number;
    player_id: number;
    called_by_auction_participant_id: number;
    status: 'open' | 'assigned' | 'unsold';
    player?: Player;
    called_by?: { id: number; name: string };
};

export type Auction = {
    id: number;
    user_id: number | null;
    name: string;
    status: AuctionStatus;
    budget_per_participant: number;
    slots_goalkeeper: number;
    slots_defender: number;
    slots_midfielder: number;
    slots_forward: number;
    current_phase: PlayerRoleValue | null;
    current_turn_auction_participant_id: number | null;
    current_call_id: number | null;
    current_turn_participant?: { id: number; name: string };
    current_call?: AuctionCall;
    participants_count?: number;
};

export type PlayerAssignment = {
    id: number;
    auction_participant_id: number;
    player_id: number;
    price: number;
    player: Player;
    participant: { id: number; name: string };
};

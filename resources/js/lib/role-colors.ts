import type { PlayerRoleValue } from '@/types/auction';

export const roleBadgeClasses: Record<PlayerRoleValue, string> = {
    P: 'border-role-goalkeeper/30 bg-role-goalkeeper/15 text-role-goalkeeper',
    D: 'border-role-defender/30 bg-role-defender/15 text-role-defender',
    C: 'border-role-midfielder/30 bg-role-midfielder/15 text-role-midfielder',
    A: 'border-role-forward/30 bg-role-forward/15 text-role-forward',
};

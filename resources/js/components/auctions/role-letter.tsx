import { roleLetterClasses } from '@/lib/role-colors';
import { cn } from '@/lib/utils';
import type { PlayerRoleValue } from '@/types/auction';

export function RoleLetter({
    role,
    className,
}: {
    role: PlayerRoleValue;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex size-4 shrink-0 items-center justify-center rounded-sm text-[10px] font-bold text-white',
                roleLetterClasses[role],
                className,
            )}
        >
            {role}
        </span>
    );
}

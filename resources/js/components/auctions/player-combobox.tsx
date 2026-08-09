import { Check, ChevronsUpDown } from 'lucide-react';
import { Fragment, useState } from 'react';
import { RoleLetter } from '@/components/auctions/role-letter';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ROLE_ORDER } from '@/lib/role-colors';
import { cn } from '@/lib/utils';
import type { Player } from '@/types/auction';

export function PlayerCombobox({
    players,
    value,
    onChange,
    placeholder = 'Cerca giocatore…',
}: {
    players: Player[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = players.find((player) => String(player.id) === value);

    const groups = ROLE_ORDER.map((role) => ({
        role,
        players: players.filter((player) => player.role === role),
    })).filter((group) => group.players.length > 0);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    <span className="truncate">
                        {selected
                            ? `${selected.name}${selected.team ? ` (${selected.team.name})` : ''}`
                            : placeholder}
                    </span>
                    <ChevronsUpDown className="opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-(--radix-popover-trigger-width) p-0">
                <Command>
                    <CommandInput placeholder={placeholder} />
                    <CommandList>
                        <CommandEmpty>Nessun giocatore trovato.</CommandEmpty>
                        {groups.map((group, index) => (
                            <Fragment key={group.role}>
                                {index > 0 && <CommandSeparator />}
                                <CommandGroup>
                                    {group.players.map((player) => (
                                        <CommandItem
                                            key={player.id}
                                            value={String(player.id)}
                                            keywords={[
                                                player.name,
                                                player.team?.name ?? '',
                                            ]}
                                            onSelect={(currentValue) => {
                                                onChange(
                                                    currentValue === value
                                                        ? ''
                                                        : currentValue,
                                                );
                                                setOpen(false);
                                            }}
                                        >
                                            <Check
                                                className={cn(
                                                    value === String(player.id)
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                            <RoleLetter role={player.role} />
                                            {player.name}
                                            {player.team
                                                ? ` (${player.team.name})`
                                                : ''}
                                            {' — FVM '}
                                            {player.fvm ?? '—'}
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            </Fragment>
                        ))}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
                        <CommandGroup>
                            {players.map((player) => (
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
                                    {player.name}
                                    {player.team
                                        ? ` (${player.team.name})`
                                        : ''}
                                    {' — FVM '}
                                    {player.fvm ?? '—'}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

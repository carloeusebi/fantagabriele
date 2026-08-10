import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { roleBadgeClasses, roleLetterClasses } from '@/lib/role-colors';
import { cn } from '@/lib/utils';
import type { Player } from '@/types/auction';

type PlayerPickerProps = {
    players: Player[];
    selectedIds: number[];
    onChange: (ids: number[]) => void;
};

export default function PlayerPicker({
    players,
    selectedIds,
    onChange,
}: PlayerPickerProps) {
    const [open, setOpen] = useState(false);

    const selectedPlayers = players.filter((player) =>
        selectedIds.includes(player.id),
    );
    const availablePlayers = players.filter(
        (player) => !selectedIds.includes(player.id),
    );

    function add(id: number) {
        onChange([...selectedIds, id]);
        setOpen(false);
    }

    function remove(id: number) {
        onChange(selectedIds.filter((selectedId) => selectedId !== id));
    }

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap gap-2">
                {selectedPlayers.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Nessun giocatore obiettivo selezionato.
                    </p>
                )}
                {selectedPlayers.map((player) => (
                    <Badge
                        key={player.id}
                        variant="outline"
                        className={cn(
                            'gap-1.5 pr-1',
                            roleBadgeClasses[player.role],
                        )}
                    >
                        {player.name}
                        {player.team && (
                            <span className="text-muted-foreground">
                                ({player.team.name})
                            </span>
                        )}
                        <button
                            type="button"
                            onClick={() => remove(player.id)}
                            className="ml-1 rounded-full hover:text-destructive"
                        >
                            <X className="size-3" />
                        </button>
                    </Badge>
                ))}
            </div>

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button type="button" variant="outline" size="sm">
                        <Plus data-icon="inline-start" />
                        Aggiungi giocatore obiettivo
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-80 p-0" align="start">
                    <Command>
                        <CommandInput placeholder="Cerca giocatore…" />
                        <CommandList>
                            <CommandEmpty>
                                Nessun giocatore trovato.
                            </CommandEmpty>
                            <CommandGroup>
                                {availablePlayers.map((player) => (
                                    <CommandItem
                                        key={player.id}
                                        value={`${player.name} ${player.team?.name ?? ''}`}
                                        onSelect={() => add(player.id)}
                                    >
                                        <span
                                            className={cn(
                                                'inline-flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold text-white',
                                                roleLetterClasses[player.role],
                                            )}
                                        >
                                            {player.role}
                                        </span>
                                        {player.name}
                                        {player.team && (
                                            <span className="ml-auto text-xs text-muted-foreground">
                                                {player.team.name}
                                            </span>
                                        )}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
}

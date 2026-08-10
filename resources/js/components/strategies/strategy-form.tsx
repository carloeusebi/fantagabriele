import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import BudgetPercentEditor from '@/components/strategies/budget-percent-editor';
import PlayerPicker from '@/components/strategies/player-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import strategies from '@/routes/strategies';
import type { Player, PlayerRoleValue } from '@/types/auction';
import type {
    Strategy,
    StrategyTypeOption,
    StrategyTypeValue,
} from '@/types/strategy';

type StrategyFormProps = {
    strategy?: Strategy;
    players: Player[];
    types: StrategyTypeOption[];
};

export default function StrategyForm({
    strategy,
    players,
    types,
}: StrategyFormProps) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: strategy?.name ?? '',
        type: strategy?.type ?? types[0].value,
        budget_goalkeeper_percent: strategy?.budget_goalkeeper_percent ?? 5,
        budget_defender_percent: strategy?.budget_defender_percent ?? 15,
        budget_midfielder_percent: strategy?.budget_midfielder_percent ?? 30,
        budget_forward_percent: strategy?.budget_forward_percent ?? 50,
        locked_roles: strategy?.locked_roles ?? ([] as PlayerRoleValue[]),
        comment: strategy?.comment ?? '',
        priority_player_ids: strategy?.priority_player_ids ?? ([] as number[]),
    });

    const percents = {
        P: data.budget_goalkeeper_percent,
        D: data.budget_defender_percent,
        C: data.budget_midfielder_percent,
        A: data.budget_forward_percent,
    };

    function handlePercentsChange(next: Record<PlayerRoleValue, number>) {
        setData((current) => ({
            ...current,
            budget_goalkeeper_percent: next.P,
            budget_defender_percent: next.D,
            budget_midfielder_percent: next.C,
            budget_forward_percent: next.A,
        }));
    }

    const selectedType = types.find((type) => type.value === data.type);

    function submit(event: FormEvent) {
        event.preventDefault();

        if (strategy) {
            put(strategies.update(strategy.id).url);
        } else {
            post(strategies.store().url);
        }
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Nome strategia</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="type">Stile</Label>
                <Select
                    value={data.type}
                    onValueChange={(value) =>
                        setData('type', value as StrategyTypeValue)
                    }
                >
                    <SelectTrigger id="type" className="w-full sm:w-64">
                        <SelectValue placeholder="Scegli uno stile" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            {types.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
                {selectedType && (
                    <p className="text-sm text-muted-foreground">
                        {selectedType.description}
                    </p>
                )}
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label>Distribuzione budget</Label>
                <BudgetPercentEditor
                    percents={percents}
                    lockedRoles={data.locked_roles}
                    onPercentsChange={handlePercentsChange}
                    onLockedRolesChange={(locked) =>
                        setData('locked_roles', locked)
                    }
                />
                <InputError message={errors.budget_goalkeeper_percent} />
            </div>

            <div className="grid gap-2">
                <Label>Giocatori obiettivo</Label>
                <PlayerPicker
                    players={players}
                    selectedIds={data.priority_player_ids}
                    onChange={(ids) => setData('priority_player_ids', ids)}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="comment">Commento libero</Label>
                <Textarea
                    id="comment"
                    value={data.comment}
                    onChange={(e) => setData('comment', e.target.value)}
                    placeholder="Note che vuoi far tenere in considerazione all'IA durante l'asta…"
                    rows={4}
                />
                <InputError message={errors.comment} />
            </div>

            <Button type="submit" disabled={processing}>
                {strategy ? 'Salva strategia' : 'Crea strategia'}
            </Button>
        </form>
    );
}

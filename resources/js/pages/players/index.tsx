import { Form, Head, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, SearchX } from 'lucide-react';
import { useMemo, useState } from 'react';
import PlayerController from '@/actions/App/Http/Controllers/PlayerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { roleBadgeClasses } from '@/lib/role-colors';
import { index as playersIndex } from '@/routes/players';
import type { Auth } from '@/types';
import type { PlayerRoleValue } from '@/types/auction';

type Team = {
    id: number;
    name: string;
};

type Player = {
    id: number;
    fanta_id: number;
    name: string;
    role: PlayerRoleValue;
    role_mantra: string | null;
    team: Team | null;
    initial_quotation: number;
    current_quotation: number;
    fvm: number | null;
};

type PageProps = {
    auth: Auth;
};

type SortKey = 'current_quotation' | 'fvm';
type SortState = { key: SortKey; direction: 'asc' | 'desc' } | null;

const roleLabels: Record<Player['role'], string> = {
    P: 'Portiere',
    D: 'Difensore',
    C: 'Centrocampista',
    A: 'Attaccante',
};

const sortLabels: Record<SortKey, string> = {
    current_quotation: 'Quotazione',
    fvm: 'FVM',
};

function normalize(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

function fuzzyMatch(query: string, text: string): boolean {
    const q = normalize(query.trim());

    if (q === '') {
        return true;
    }

    const t = normalize(text);
    let queryIndex = 0;

    for (let i = 0; i < t.length && queryIndex < q.length; i++) {
        if (t[i] === q[queryIndex]) {
            queryIndex++;
        }
    }

    return queryIndex === q.length;
}

export default function PlayersIndex({ players }: { players: Player[] }) {
    const { auth } = usePage<PageProps>().props;
    const [search, setSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState<'all' | Player['role']>('all');
    const [sort, setSort] = useState<SortState>(null);

    const filteredPlayers = useMemo(() => {
        let result = players;

        if (roleFilter !== 'all') {
            result = result.filter((player) => player.role === roleFilter);
        }

        if (search.trim() !== '') {
            result = result.filter(
                (player) =>
                    fuzzyMatch(search, player.name) ||
                    fuzzyMatch(search, player.team?.name ?? ''),
            );
        }

        if (sort) {
            result = [...result].sort((a, b) => {
                const diff = (a[sort.key] ?? 0) - (b[sort.key] ?? 0);

                return sort.direction === 'asc' ? diff : -diff;
            });
        }

        return result;
    }, [players, search, roleFilter, sort]);

    function toggleSort(key: SortKey) {
        setSort((current) => {
            if (current?.key !== key) {
                return { key, direction: 'desc' };
            }

            if (current.direction === 'desc') {
                return { key, direction: 'asc' };
            }

            return null;
        });
    }

    function sortIcon(key: SortKey) {
        if (sort?.key !== key) {
            return <ArrowUpDown className="size-3.5" />;
        }

        return sort.direction === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    }

    return (
        <>
            <Head title="Giocatori" />

            <div className="flex flex-col gap-4 p-4 sm:gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Giocatori"
                        description={`${filteredPlayers.length} di ${players.length} giocatori`}
                    />

                    {auth.user.is_admin && (
                        <Form
                            {...PlayerController.import.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            className="flex flex-col items-stretch gap-2 sm:flex-row sm:items-start"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <input
                                            type="file"
                                            name="file"
                                            accept=".xlsx"
                                            required
                                            className="w-full text-sm text-muted-foreground file:mr-2 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-secondary-foreground"
                                        />
                                        <InputError
                                            className="mt-1"
                                            message={errors.file}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full sm:w-auto"
                                    >
                                        Importa da file
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </div>

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cerca per nome o squadra…"
                        className="sm:max-w-xs"
                    />

                    <Select
                        value={roleFilter}
                        onValueChange={(value) =>
                            setRoleFilter(value as 'all' | Player['role'])
                        }
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="Ruolo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="all">
                                    Tutti i ruoli
                                </SelectItem>
                                {(
                                    Object.keys(roleLabels) as Array<
                                        Player['role']
                                    >
                                ).map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {roleLabels[role]}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Ruolo</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Squadra
                                </TableHead>
                                {(['current_quotation', 'fvm'] as const).map(
                                    (key) => (
                                        <TableHead
                                            key={key}
                                            className={
                                                key === 'fvm'
                                                    ? 'hidden sm:table-cell'
                                                    : undefined
                                            }
                                        >
                                            <button
                                                type="button"
                                                onClick={() => toggleSort(key)}
                                                className="inline-flex items-center gap-1 hover:text-foreground"
                                            >
                                                {sortLabels[key]}
                                                {sortIcon(key)}
                                            </button>
                                        </TableHead>
                                    ),
                                )}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredPlayers.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5}>
                                        <Empty>
                                            <EmptyHeader>
                                                <EmptyMedia variant="icon">
                                                    <SearchX />
                                                </EmptyMedia>
                                                <EmptyTitle>
                                                    Nessun giocatore trovato
                                                </EmptyTitle>
                                                <EmptyDescription>
                                                    Prova a modificare la
                                                    ricerca o il filtro per
                                                    ruolo.
                                                </EmptyDescription>
                                            </EmptyHeader>
                                        </Empty>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                filteredPlayers.map((player) => (
                                    <TableRow key={player.id}>
                                        <TableCell className="font-medium">
                                            {player.name}
                                            <div className="text-xs text-muted-foreground sm:hidden">
                                                {player.team?.name ?? '—'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    roleBadgeClasses[
                                                        player.role
                                                    ]
                                                }
                                            >
                                                {roleLabels[player.role]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {player.team?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {player.current_quotation}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {player.fvm ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

PlayersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Giocatori',
            href: playersIndex(),
        },
    ],
};

import { Head, router, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { AdvisorStrategyBrief } from '@/components/auctions/advisor-strategy-brief';
import { AssignmentHistory } from '@/components/auctions/assignment-history';
import {
    AuctionProvider,
    useAuction,
} from '@/components/auctions/auction-context';
import { CallOrderEditor } from '@/components/auctions/call-order-editor';
import { CurrentCallPanel } from '@/components/auctions/current-call-panel';
import { ParticipantBoard } from '@/components/auctions/participant-board';
import { RealtimeEventLogPanel } from '@/components/auctions/realtime-event-log-panel';
import { RoleChooser } from '@/components/auctions/role-chooser';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAdvisorStrategy } from '@/hooks/use-advisor-strategy';
import { useAuctionChannel } from '@/hooks/use-auction-channel';
import { useAuctionPresence } from '@/hooks/use-auction-presence';
import auctions from '@/routes/auctions';
import auctionAdvisor from '@/routes/auctions/advisor';
import auctionParticipants from '@/routes/auctions/participants';
import auctionStatus from '@/routes/auctions/status';
import type { Auth } from '@/types';
import type {
    AdvisorEntry,
    Auction,
    AuctionParticipant,
    AuctionPermissions,
    AuctionViewer,
    Player,
    PlayerAssignment,
    RoleOption,
} from '@/types/auction';

const statusLabels: Record<Auction['status'], string> = {
    setup: 'In preparazione',
    in_progress: 'In corso',
    paused: 'In pausa',
    completed: 'Conclusa',
};

type PageProps = {
    auth: Auth;
};

type LockedAuction = {
    id: number;
    name: string;
};

type AuctionsShowProps =
    | {
          locked: true;
          auction: LockedAuction;
      }
    | {
          locked: false;
          auction: Auction;
          participants: AuctionParticipant[];
          availablePlayers: Player[];
          unassignedPlayers: Player[];
          assignments: PlayerAssignment[];
          advisorEntries: AdvisorEntry[];
          viewer: AuctionViewer | null;
          permissions: AuctionPermissions;
          roles: RoleOption[];
      };

export default function AuctionsShow(props: AuctionsShowProps) {
    if (props.locked) {
        return <LockedAuctionGate auction={props.auction} />;
    }

    return <UnlockedAuctionShow {...props} />;
}

function LockedAuctionGate({ auction }: { auction: LockedAuction }) {
    const [password, setPassword] = useState('');
    const [processing, setProcessing] = useState(false);

    function submit(event: FormEvent) {
        event.preventDefault();

        if (!password) {
            return;
        }

        setProcessing(true);

        router.post(
            auctions.unlock(auction.id).url,
            { password },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title={auction.name} />

            <Card className="max-w-md">
                <CardHeader>
                    <CardTitle>{auction.name}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Questa asta è protetta da password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                autoFocus
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Sblocca
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

function UnlockedAuctionShow({
    auction,
    participants,
    availablePlayers,
    unassignedPlayers,
    assignments,
    advisorEntries,
    viewer,
    permissions,
    roles,
}: {
    auction: Auction;
    participants: AuctionParticipant[];
    availablePlayers: Player[];
    unassignedPlayers: Player[];
    assignments: PlayerAssignment[];
    advisorEntries: AdvisorEntry[];
    viewer: AuctionViewer | null;
    permissions: AuctionPermissions;
    roles: RoleOption[];
}) {
    useAuctionChannel(auction.id);
    const online = useAuctionPresence(auction.id);

    const { auth } = usePage<PageProps>().props;
    const isOwner = auction.user_id === auth.user.id;

    const currentPhaseLabel = roles.find(
        (role) => role.value === auction.current_phase,
    )?.label;

    const strategy = useAdvisorStrategy(
        auctionAdvisor.strategy(auction.id).url,
    );
    const latestStrategyEntry =
        [...advisorEntries].reverse().find((e) => e.kind === 'strategy') ??
        null;

    useEffect(() => {
        if (
            auction.status === 'in_progress' &&
            permissions.advise &&
            latestStrategyEntry === null &&
            !strategy.loading
        ) {
            strategy.generate();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [auction.status, permissions.advise, latestStrategyEntry]);

    return (
        <AuctionProvider
            value={{
                auction,
                participants,
                availablePlayers,
                unassignedPlayers,
                assignments,
                advisorEntries,
                viewer,
                permissions,
                roles,
                online,
                isOwner,
            }}
        >
            <Head title={auction.name} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title={auction.name}
                        description={
                            auction.status === 'in_progress' &&
                            currentPhaseLabel
                                ? `Fase: ${currentPhaseLabel} — turno di ${auction.current_turn_participant?.name ?? '—'}`
                                : statusLabels[auction.status]
                        }
                    />

                    <div className="flex items-center gap-2">
                        <Badge variant="outline">
                            {statusLabels[auction.status]}
                        </Badge>
                        {permissions.updateStatus && <StatusControls />}
                        {isOwner && <DeleteAuctionDialog />}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="flex min-w-0 flex-col gap-6 lg:col-span-2">
                        {viewer === null ? (
                            <JoinAuctionGate />
                        ) : (
                            <>
                                <ViewerIndicator />

                                {auction.status === 'setup' &&
                                    (permissions.manage ? (
                                        <SetupPanel />
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            In attesa che l'organizzatore
                                            configuri l'asta.
                                        </p>
                                    ))}

                                {auction.status !== 'setup' && (
                                    <>
                                        {(latestStrategyEntry ||
                                            strategy.loading) && (
                                            <AdvisorStrategyBrief
                                                entry={latestStrategyEntry}
                                                loading={strategy.loading}
                                            />
                                        )}

                                        <CurrentCallPanel />

                                        <ParticipantBoard />

                                        <div className="min-w-0">
                                            <Heading
                                                variant="small"
                                                title="Storico assegnazioni"
                                            />
                                            <div className="mt-3">
                                                <AssignmentHistory />
                                            </div>
                                        </div>
                                    </>
                                )}
                            </>
                        )}
                    </div>

                    <div className="lg:col-span-1">
                        <RealtimeEventLogPanel />
                    </div>
                </div>
            </div>
        </AuctionProvider>
    );
}

function JoinAuctionGate() {
    return (
        <Card className="max-w-md">
            <CardHeader>
                <CardTitle>Come vuoi partecipare?</CardTitle>
            </CardHeader>
            <CardContent>
                <RoleChooser />
            </CardContent>
        </Card>
    );
}

function ViewerIndicator() {
    const { participants, viewer, online } = useAuction();

    if (viewer === null) {
        return null;
    }

    const participant = participants.find(
        (p) => p.id === viewer.auction_participant_id,
    );

    return (
        <div className="flex flex-col gap-2 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-2">
                <span>
                    {participant
                        ? `Tu sei: ${participant.name}`
                        : 'Stai guardando come spettatore'}
                </span>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button type="button" variant="ghost" size="icon">
                            <Pencil />
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Cambia il tuo ruolo</DialogTitle>
                        <RoleChooser />
                    </DialogContent>
                </Dialog>
            </div>

            {online.length > 0 && (
                <div className="flex flex-wrap items-center gap-3">
                    {online.map((user) => (
                        <span
                            key={user.id}
                            className="flex items-center gap-1.5"
                        >
                            <span className="size-2 shrink-0 rounded-full bg-green-500" />
                            {user.name}
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
}

function StatusControls() {
    const { auction } = useAuction();

    function update(action: 'start' | 'pause' | 'resume') {
        // "start" also assigns the first phase and turn server-side, so it
        // isn't safe to predict optimistically — only pause/resume flip a
        // single, already-known field.
        if (action === 'start') {
            router.patch(auctionStatus.update(auction.id).url, { action });

            return;
        }

        router
            .optimistic<{ auction: Auction }>((props) => ({
                auction: {
                    ...props.auction,
                    status: action === 'pause' ? 'paused' : 'in_progress',
                },
            }))
            .patch(auctionStatus.update(auction.id).url, { action });
    }

    if (auction.status === 'setup') {
        return <Button onClick={() => update('start')}>Avvia asta</Button>;
    }

    if (auction.status === 'in_progress') {
        return (
            <Button variant="outline" onClick={() => update('pause')}>
                Pausa
            </Button>
        );
    }

    if (auction.status === 'paused') {
        return <Button onClick={() => update('resume')}>Riprendi</Button>;
    }

    return null;
}

function DeleteAuctionDialog() {
    const { auction } = useAuction();

    function destroy() {
        router.delete(auctions.destroy(auction.id).url);
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="ghost" size="icon">
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Eliminare "{auction.name}"?</DialogTitle>
                <p className="text-sm text-muted-foreground">
                    Verranno eliminati anche partecipanti, chiamate e
                    assegnazioni di questa asta. L'operazione non è reversibile.
                </p>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Annulla
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={destroy}
                    >
                        Elimina
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function SetupPanel() {
    const { auction, participants } = useAuction();
    const [name, setName] = useState('');

    function addParticipant(event: FormEvent) {
        event.preventDefault();

        if (!name.trim()) {
            return;
        }

        router.post(
            auctionParticipants.store(auction.id).url,
            { name },
            { preserveScroll: true, onSuccess: () => setName('') },
        );
    }

    function removeParticipant(participantId: number) {
        router.delete(
            auctionParticipants.destroy([auction.id, participantId]).url,
            { preserveScroll: true },
        );
    }

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div className="space-y-3">
                <Heading variant="small" title="Partecipanti" />

                <form onSubmit={addParticipant} className="flex gap-2">
                    <Input
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="Nome partecipante"
                    />
                    <Button type="submit">Aggiungi</Button>
                </form>

                <div className="space-y-2">
                    {participants.map((participant) => (
                        <div
                            key={participant.id}
                            className="flex items-center justify-between gap-2 rounded-md border border-sidebar-border/70 p-2 dark:border-sidebar-border"
                        >
                            <div className="flex items-center gap-2">
                                <span className="font-medium">
                                    {participant.name}
                                </span>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        removeParticipant(participant.id)
                                    }
                                >
                                    Rimuovi
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="space-y-3">
                <Heading variant="small" title="Ordine di chiamata" />
                <CallOrderEditor />
            </div>
        </div>
    );
}

AuctionsShow.layout = {
    breadcrumbs: [{ title: 'Aste', href: auctions.index() }],
};

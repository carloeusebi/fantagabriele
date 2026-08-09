import { Head, router, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AssignmentHistory } from '@/components/auctions/assignment-history';
import { CallOrderEditor } from '@/components/auctions/call-order-editor';
import { CurrentCallPanel } from '@/components/auctions/current-call-panel';
import { ParticipantBoard } from '@/components/auctions/participant-board';
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
import { useAuctionChannel } from '@/hooks/use-auction-channel';
import { useAuctionPresence } from '@/hooks/use-auction-presence';
import auctions from '@/routes/auctions';
import auctionParticipants from '@/routes/auctions/participants';
import auctionStatus from '@/routes/auctions/status';
import type { Auth } from '@/types';
import type {
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

export default function AuctionsShow({
    auction,
    participants,
    availablePlayers,
    assignments,
    viewer,
    permissions,
    roles,
}: {
    auction: Auction;
    participants: AuctionParticipant[];
    availablePlayers: Player[];
    assignments: PlayerAssignment[];
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

    return (
        <>
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
                        {permissions.updateStatus && (
                            <StatusControls auction={auction} />
                        )}
                        {isOwner && <DeleteAuctionDialog auction={auction} />}
                    </div>
                </div>

                {viewer === null ? (
                    <JoinAuctionGate
                        auctionId={auction.id}
                        participants={participants}
                    />
                ) : (
                    <>
                        <ViewerIndicator
                            auctionId={auction.id}
                            participants={participants}
                            viewer={viewer}
                            online={online}
                        />

                        {auction.status === 'setup' &&
                            (permissions.manage ? (
                                <SetupPanel
                                    auction={auction}
                                    participants={participants}
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    In attesa che l'organizzatore configuri
                                    l'asta.
                                </p>
                            ))}

                        {auction.status !== 'setup' && (
                            <>
                                <CurrentCallPanel
                                    auction={auction}
                                    participants={participants}
                                    availablePlayers={availablePlayers}
                                    canCall={permissions.call}
                                    canResolve={permissions.resolveCall}
                                    canAdvise={permissions.advise}
                                />

                                <ParticipantBoard
                                    participants={participants}
                                    currentTurnParticipantId={
                                        auction.current_turn_auction_participant_id
                                    }
                                    onlineUserIds={online.map((u) => u.id)}
                                />

                                <div>
                                    <Heading
                                        variant="small"
                                        title="Storico assegnazioni"
                                    />
                                    <div className="mt-3">
                                        <AssignmentHistory
                                            auctionId={auction.id}
                                            assignments={assignments}
                                            participants={participants}
                                            canResolve={permissions.resolveCall}
                                        />
                                    </div>
                                </div>
                            </>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function JoinAuctionGate({
    auctionId,
    participants,
}: {
    auctionId: number;
    participants: AuctionParticipant[];
}) {
    return (
        <Card className="max-w-md">
            <CardHeader>
                <CardTitle>Come vuoi partecipare?</CardTitle>
            </CardHeader>
            <CardContent>
                <RoleChooser
                    auctionId={auctionId}
                    participants={participants}
                    currentParticipantId={null}
                />
            </CardContent>
        </Card>
    );
}

function ViewerIndicator({
    auctionId,
    participants,
    viewer,
    online,
}: {
    auctionId: number;
    participants: AuctionParticipant[];
    viewer: AuctionViewer;
    online: { id: number; name: string }[];
}) {
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
                        <RoleChooser
                            auctionId={auctionId}
                            participants={participants}
                            currentParticipantId={viewer.auction_participant_id}
                        />
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

function StatusControls({ auction }: { auction: Auction }) {
    function update(action: 'start' | 'pause' | 'resume') {
        router.patch(auctionStatus.update(auction.id).url, { action });
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

function DeleteAuctionDialog({ auction }: { auction: Auction }) {
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

function SetupPanel({
    auction,
    participants,
}: {
    auction: Auction;
    participants: AuctionParticipant[];
}) {
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

    function designateAgent(participantId: number) {
        router.patch(
            auctionParticipants.update([auction.id, participantId]).url,
            { is_agent: true },
            { preserveScroll: true },
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
                                {participant.is_agent && (
                                    <Badge variant="outline">IA</Badge>
                                )}
                            </div>
                            <div className="flex gap-2">
                                {!participant.is_agent && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            designateAgent(participant.id)
                                        }
                                    >
                                        Gestito dall'IA
                                    </Button>
                                )}
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
                <CallOrderEditor
                    auctionId={auction.id}
                    participants={participants}
                />
            </div>
        </div>
    );
}

AuctionsShow.layout = {
    breadcrumbs: [{ title: 'Aste', href: auctions.index() }],
};

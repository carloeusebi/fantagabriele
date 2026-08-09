import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AssignmentHistory } from '@/components/auctions/assignment-history';
import { CallOrderEditor } from '@/components/auctions/call-order-editor';
import { CurrentCallPanel } from '@/components/auctions/current-call-panel';
import { ParticipantBoard } from '@/components/auctions/participant-board';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuctionChannel } from '@/hooks/use-auction-channel';
import auctions from '@/routes/auctions';
import auctionParticipants from '@/routes/auctions/participants';
import auctionStatus from '@/routes/auctions/status';
import type {
    Auction,
    AuctionParticipant,
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

export default function AuctionsShow({
    auction,
    participants,
    availablePlayers,
    assignments,
    roles,
}: {
    auction: Auction;
    participants: AuctionParticipant[];
    availablePlayers: Player[];
    assignments: PlayerAssignment[];
    roles: RoleOption[];
}) {
    useAuctionChannel(auction.id);

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
                        <StatusControls auction={auction} />
                    </div>
                </div>

                {auction.status === 'setup' && (
                    <SetupPanel auction={auction} participants={participants} />
                )}

                {auction.status !== 'setup' && (
                    <>
                        <CurrentCallPanel
                            auction={auction}
                            participants={participants}
                            availablePlayers={availablePlayers}
                        />

                        <ParticipantBoard
                            participants={participants}
                            currentTurnParticipantId={
                                auction.current_turn_auction_participant_id
                            }
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
                                />
                            </div>
                        </div>
                    </>
                )}
            </div>
        </>
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

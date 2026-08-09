import { router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { PlayerCombobox } from '@/components/auctions/player-combobox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { useAdvisorStream } from '@/hooks/use-advisor-stream';
import { hasCompletedRole } from '@/lib/auction';
import auctionAdvisor from '@/routes/auctions/advisor';
import auctionAssignments from '@/routes/auctions/assignments';
import auctionCall from '@/routes/auctions/call';
import type {
    Auction,
    AuctionParticipant,
    Player,
    PlayerRoleValue,
} from '@/types/auction';

type CallAdvice = { player_id: number; reasoning: string; is_bluff: boolean };
type BidAdvice = { max_price: number; reasoning: string; is_bluff: boolean };

function AdvisorReasoning({
    reasoning,
    streaming,
    error,
    isBluff,
}: {
    reasoning: string;
    streaming: boolean;
    error: string | null;
    isBluff?: boolean;
}) {
    if (error) {
        return <p className="text-sm text-destructive">{error}</p>;
    }

    if (streaming && !reasoning) {
        return (
            <p className="text-sm text-muted-foreground italic">
                L'IA sta pensando…
            </p>
        );
    }

    if (!reasoning) {
        return null;
    }

    return (
        <div className="space-y-1">
            {isBluff && <Badge variant="destructive">Bluff</Badge>}
            <p className="text-sm text-muted-foreground">{reasoning}</p>
        </div>
    );
}

export function CurrentCallPanel({
    auction,
    participants,
    availablePlayers,
    canCall,
    canResolve,
    canAdviseCall,
    canAdviseBid,
}: {
    auction: Auction;
    participants: AuctionParticipant[];
    availablePlayers: Player[];
    canCall: boolean;
    canResolve: boolean;
    canAdviseCall: boolean;
    canAdviseBid: boolean;
}) {
    const call = auction.current_call;

    if (call) {
        return (
            <ResolveCallForm
                auctionId={auction.id}
                call={call}
                participants={participants}
                currentPhase={auction.current_phase}
                canAskForBidAdvice={canAdviseBid}
                canResolve={canResolve}
            />
        );
    }

    if (auction.status !== 'in_progress' || !auction.current_phase) {
        return null;
    }

    return (
        <OpenCallForm
            auctionId={auction.id}
            defaultCallerId={auction.current_turn_participant?.id ?? null}
            participants={participants}
            currentPhase={auction.current_phase}
            availablePlayers={availablePlayers}
            canAskForCallAdvice={canAdviseCall}
            canCall={canCall}
        />
    );
}

function OpenCallForm({
    auctionId,
    defaultCallerId,
    participants,
    currentPhase,
    availablePlayers,
    canAskForCallAdvice,
    canCall,
}: {
    auctionId: number;
    defaultCallerId: number | null;
    participants: AuctionParticipant[];
    currentPhase: PlayerRoleValue | null;
    availablePlayers: Player[];
    canAskForCallAdvice: boolean;
    canCall: boolean;
}) {
    const [playerId, setPlayerId] = useState<string>('');
    const [callerId, setCallerId] = useState<string>(
        defaultCallerId ? String(defaultCallerId) : '',
    );
    const advisor = useAdvisorStream<CallAdvice>();

    function askForAdvice() {
        advisor.start(auctionAdvisor.call(auctionId).url);
    }

    useEffect(() => {
        if (advisor.result) {
            setPlayerId(String(advisor.result.player_id));
            router.reload({ only: ['advisorEntries'] });
        }
    }, [advisor.result]);

    function submit(event: FormEvent) {
        event.preventDefault();

        if (!playerId || !callerId) {
            return;
        }

        router.post(
            auctionCall.store(auctionId).url,
            {
                player_id: Number(playerId),
                called_by_auction_participant_id: Number(callerId),
            },
            { preserveScroll: true },
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Chiama un giocatore</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {canAskForCallAdvice && (
                    <div className="space-y-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={advisor.streaming}
                            onClick={askForAdvice}
                        >
                            <Sparkles data-icon="inline-start" />
                            Chiedi consiglio all'IA
                        </Button>

                        <AdvisorReasoning
                            reasoning={advisor.reasoning}
                            streaming={advisor.streaming}
                            error={advisor.error}
                            isBluff={advisor.result?.is_bluff}
                        />
                    </div>
                )}

                {canCall ? (
                    <form
                        onSubmit={submit}
                        className="flex flex-col gap-4 sm:flex-row sm:items-end"
                    >
                        <div className="grid flex-1 gap-2">
                            <Label>Giocatore</Label>
                            <PlayerCombobox
                                players={availablePlayers}
                                value={playerId}
                                onChange={setPlayerId}
                            />
                        </div>

                        <div className="grid gap-2 sm:w-56">
                            <Label>Chiamato da</Label>
                            <Select
                                value={callerId}
                                onValueChange={setCallerId}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Partecipante" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        {participants.map((participant) => (
                                            <SelectItem
                                                key={participant.id}
                                                value={String(participant.id)}
                                                disabled={hasCompletedRole(
                                                    participant,
                                                    currentPhase,
                                                )}
                                            >
                                                {participant.name}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button type="submit">Chiama</Button>
                    </form>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Puoi chiamare un giocatore solo durante il tuo turno.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function ResolveCallForm({
    auctionId,
    call,
    participants,
    currentPhase,
    canAskForBidAdvice,
    canResolve,
}: {
    auctionId: number;
    call: NonNullable<Auction['current_call']>;
    participants: AuctionParticipant[];
    currentPhase: PlayerRoleValue | null;
    canAskForBidAdvice: boolean;
    canResolve: boolean;
}) {
    const [participantId, setParticipantId] = useState('');
    const [price, setPrice] = useState('');
    const advisor = useAdvisorStream<BidAdvice>();

    function askForAdvice() {
        advisor.start(auctionAdvisor.bid(auctionId).url);
    }

    useEffect(() => {
        if (advisor.result) {
            setPrice(String(advisor.result.max_price));
            router.reload({ only: ['advisorEntries'] });
        }
    }, [advisor.result]);

    function assign(event: FormEvent) {
        event.preventDefault();

        if (!participantId || !price) {
            return;
        }

        router.post(
            auctionAssignments.store(auctionId).url,
            {
                auction_participant_id: Number(participantId),
                price: Number(price),
            },
            { preserveScroll: true },
        );
    }

    function unsold() {
        router.delete(auctionCall.destroy(auctionId).url, {
            preserveScroll: true,
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    In asta: {call.player?.name}
                    {call.player?.team ? ` (${call.player.team.name})` : ''}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="text-sm text-muted-foreground">
                    Chiamato da {call.called_by?.name}
                </p>

                {canAskForBidAdvice && (
                    <div className="space-y-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={advisor.streaming}
                            onClick={askForAdvice}
                        >
                            <Sparkles data-icon="inline-start" />
                            Quanto offrire?
                        </Button>

                        {advisor.result && (
                            <p className="text-sm font-medium">
                                Tetto massimo consigliato:{' '}
                                {advisor.result.max_price} crediti
                            </p>
                        )}

                        <AdvisorReasoning
                            reasoning={advisor.reasoning}
                            streaming={advisor.streaming}
                            error={advisor.error}
                            isBluff={advisor.result?.is_bluff}
                        />
                    </div>
                )}

                {canResolve ? (
                    <form
                        onSubmit={assign}
                        className="flex flex-col gap-4 sm:flex-row sm:items-end"
                    >
                        <div className="grid gap-2 sm:w-56">
                            <Label>Aggiudicato a</Label>
                            <Select
                                value={participantId}
                                onValueChange={setParticipantId}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Partecipante" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        {participants.map((participant) => (
                                            <SelectItem
                                                key={participant.id}
                                                value={String(participant.id)}
                                                disabled={hasCompletedRole(
                                                    participant,
                                                    currentPhase,
                                                )}
                                            >
                                                {participant.name}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Prezzo</Label>
                            <Input
                                type="number"
                                min={1}
                                value={price}
                                onChange={(e) => setPrice(e.target.value)}
                                className="w-28"
                            />
                        </div>

                        <Button type="submit">Registra</Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={unsold}
                        >
                            Nessuno se lo aggiudica
                        </Button>
                    </form>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Solo l'admin può registrare l'esito della chiamata.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

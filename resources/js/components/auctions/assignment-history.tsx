import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import auctionAssignments from '@/routes/auctions/assignments';
import type { AuctionParticipant, PlayerAssignment } from '@/types/auction';

export function AssignmentHistory({
    auctionId,
    assignments,
    participants,
    canAct,
}: {
    auctionId: number;
    assignments: PlayerAssignment[];
    participants: AuctionParticipant[];
    canAct: boolean;
}) {
    if (assignments.length === 0) {
        return (
            <Empty>
                <EmptyTitle>Nessuna assegnazione</EmptyTitle>
                <EmptyDescription>
                    Le assegnazioni registrate durante l'asta compariranno qui.
                </EmptyDescription>
            </Empty>
        );
    }

    return (
        <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Giocatore</TableHead>
                        <TableHead>Assegnato a</TableHead>
                        <TableHead>Prezzo</TableHead>
                        <TableHead />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {assignments.map((assignment) => (
                        <TableRow key={assignment.id}>
                            <TableCell>
                                {assignment.player.name}
                                {assignment.player.team
                                    ? ` (${assignment.player.team.name})`
                                    : ''}
                            </TableCell>
                            <TableCell>{assignment.participant.name}</TableCell>
                            <TableCell>{assignment.price}</TableCell>
                            <TableCell className="flex justify-end gap-2">
                                {canAct && (
                                    <>
                                        <CorrectAssignmentDialog
                                            auctionId={auctionId}
                                            assignment={assignment}
                                            participants={participants}
                                        />
                                        <RevertAssignmentDialog
                                            auctionId={auctionId}
                                            assignment={assignment}
                                        />
                                    </>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function CorrectAssignmentDialog({
    auctionId,
    assignment,
    participants,
}: {
    auctionId: number;
    assignment: PlayerAssignment;
    participants: AuctionParticipant[];
}) {
    const [participantId, setParticipantId] = useState(
        String(assignment.auction_participant_id),
    );
    const [price, setPrice] = useState(String(assignment.price));

    function submit(event: FormEvent) {
        event.preventDefault();

        router.patch(
            auctionAssignments.update([auctionId, assignment.id]).url,
            {
                auction_participant_id: Number(participantId),
                price: Number(price),
            },
            { preserveScroll: true },
        );
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    Correggi
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Correggi assegnazione</DialogTitle>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label>Assegnato a</Label>
                        <Select
                            value={participantId}
                            onValueChange={setParticipantId}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    {participants.map((participant) => (
                                        <SelectItem
                                            key={participant.id}
                                            value={String(participant.id)}
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
                        />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Annulla
                            </Button>
                        </DialogClose>
                        <Button type="submit">Salva correzione</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RevertAssignmentDialog({
    auctionId,
    assignment,
}: {
    auctionId: number;
    assignment: PlayerAssignment;
}) {
    function revert() {
        router.delete(
            auctionAssignments.destroy([auctionId, assignment.id]).url,
            { preserveScroll: true },
        );
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="ghost" size="sm">
                    Annulla
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Annullare questa assegnazione?</DialogTitle>
                <p className="text-sm text-muted-foreground">
                    {assignment.player.name} tornerà disponibile e i crediti
                    verranno restituiti a {assignment.participant.name}.
                </p>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Chiudi
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={revert}
                    >
                        Conferma annullamento
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

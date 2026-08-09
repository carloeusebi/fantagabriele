import { Head, Link } from '@inertiajs/react';
import { Gavel, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import auctions from '@/routes/auctions';
import type { Auction } from '@/types/auction';

const statusLabels: Record<Auction['status'], string> = {
    setup: 'In preparazione',
    in_progress: 'In corso',
    paused: 'In pausa',
    completed: 'Conclusa',
};

const statusVariants: Record<
    Auction['status'],
    'outline' | 'default' | 'secondary'
> = {
    setup: 'outline',
    in_progress: 'default',
    paused: 'secondary',
    completed: 'secondary',
};

export default function AuctionsIndex({
    auctions: list,
}: {
    auctions: Auction[];
}) {
    return (
        <>
            <Head title="Aste" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading title="Aste" description={`${list.length} aste`} />

                    <Button asChild>
                        <Link href={auctions.create()}>
                            <Plus data-icon="inline-start" />
                            Nuova asta
                        </Link>
                    </Button>
                </div>

                {list.length === 0 ? (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Gavel />
                            </EmptyMedia>
                            <EmptyTitle>Nessuna asta</EmptyTitle>
                            <EmptyDescription>
                                Crea la tua prima asta per iniziare.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nome</TableHead>
                                    <TableHead>Stato</TableHead>
                                    <TableHead>Partecipanti</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {list.map((auction) => (
                                    <TableRow key={auction.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={auctions.show(auction.id)}
                                                className="hover:underline"
                                            >
                                                {auction.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariants[
                                                        auction.status
                                                    ]
                                                }
                                            >
                                                {statusLabels[auction.status]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {auction.participants_count}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

AuctionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Aste',
            href: auctions.index(),
        },
    ],
};

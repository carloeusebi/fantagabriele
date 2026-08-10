import { Head, Link, router } from '@inertiajs/react';
import { ListChecks, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import strategies from '@/routes/strategies';
import type { Strategy } from '@/types/strategy';

export default function StrategiesIndex({
    strategies: list,
}: {
    strategies: Strategy[];
}) {
    return (
        <>
            <Head title="Strategie" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Strategie"
                        description={`${list.length} strategie salvate`}
                    />

                    <Button asChild>
                        <Link href={strategies.create()}>
                            <Plus data-icon="inline-start" />
                            Nuova strategia
                        </Link>
                    </Button>
                </div>

                {list.length === 0 ? (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <ListChecks />
                            </EmptyMedia>
                            <EmptyTitle>Nessuna strategia</EmptyTitle>
                            <EmptyDescription>
                                Crea la tua prima strategia per usarla nella
                                prossima asta.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nome</TableHead>
                                    <TableHead>Stile</TableHead>
                                    <TableHead>Budget (P/D/C/A)</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {list.map((strategy) => (
                                    <TableRow key={strategy.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={strategies.edit(
                                                    strategy.id,
                                                )}
                                                className="hover:underline"
                                            >
                                                {strategy.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {typeLabels[strategy.type]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground tabular-nums">
                                            {strategy.budget_goalkeeper_percent}
                                            % /{' '}
                                            {strategy.budget_defender_percent}%
                                            /{' '}
                                            {strategy.budget_midfielder_percent}
                                            % /{' '}
                                            {strategy.budget_forward_percent}%
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <DeleteStrategyDialog
                                                strategy={strategy}
                                            />
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

const typeLabels: Record<Strategy['type'], string> = {
    diversified: 'Diversificata',
    balanced: 'Bilanciato',
    superstars: 'Fuoriclasse',
};

function DeleteStrategyDialog({ strategy }: { strategy: Strategy }) {
    function destroy() {
        router.delete(strategies.destroy(strategy.id).url);
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" variant="ghost" size="icon">
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Eliminare "{strategy.name}"?</DialogTitle>
                <p className="text-sm text-muted-foreground">
                    L'operazione non è reversibile.
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

StrategiesIndex.layout = {
    breadcrumbs: [{ title: 'Strategie', href: strategies.index() }],
};

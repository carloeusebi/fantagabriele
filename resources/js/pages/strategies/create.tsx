import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import StrategyForm from '@/components/strategies/strategy-form';
import strategies, { create as strategiesCreate } from '@/routes/strategies';
import type { Player } from '@/types/auction';
import type { StrategyTypeOption } from '@/types/strategy';

export default function StrategiesCreate({
    players,
    types,
}: {
    players: Player[];
    types: StrategyTypeOption[];
}) {
    return (
        <>
            <Head title="Nuova strategia" />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Nuova strategia"
                    description="Definisci budget, obiettivi e stile per la prossima asta"
                />

                <StrategyForm players={players} types={types} />
            </div>
        </>
    );
}

StrategiesCreate.layout = {
    breadcrumbs: [
        { title: 'Strategie', href: strategies.index() },
        { title: 'Nuova strategia', href: strategiesCreate() },
    ],
};

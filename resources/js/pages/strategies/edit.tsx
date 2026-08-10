import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import StrategyForm from '@/components/strategies/strategy-form';
import strategies from '@/routes/strategies';
import type { Player } from '@/types/auction';
import type { Strategy, StrategyTypeOption } from '@/types/strategy';

export default function StrategiesEdit({
    strategy,
    players,
    types,
}: {
    strategy: Strategy;
    players: Player[];
    types: StrategyTypeOption[];
}) {
    return (
        <>
            <Head title={`Strategia: ${strategy.name}`} />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title={strategy.name}
                    description="Modifica budget, obiettivi e stile"
                />

                <StrategyForm
                    strategy={strategy}
                    players={players}
                    types={types}
                />
            </div>
        </>
    );
}

StrategiesEdit.layout = {
    breadcrumbs: [{ title: 'Strategie', href: strategies.index() }],
};

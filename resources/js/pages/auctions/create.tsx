import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import auctions, { create as auctionsCreate } from '@/routes/auctions';

type ParticipantInput = {
    name: string;
    is_agent: boolean;
};

export default function AuctionsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        budget_per_participant: 500,
        slots_goalkeeper: 3,
        slots_defender: 8,
        slots_midfielder: 8,
        slots_forward: 6,
        participants: [
            { name: '', is_agent: true },
            { name: '', is_agent: false },
        ] as ParticipantInput[],
    });

    function updateParticipant(
        index: number,
        patch: Partial<ParticipantInput>,
    ) {
        setData(
            'participants',
            data.participants.map((participant, i) =>
                i === index ? { ...participant, ...patch } : participant,
            ),
        );
    }

    function addParticipant() {
        setData('participants', [
            ...data.participants,
            { name: '', is_agent: false },
        ]);
    }

    function removeParticipant(index: number) {
        setData(
            'participants',
            data.participants.filter((_, i) => i !== index),
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        post(auctions.store().url);
    }

    return (
        <>
            <Head title="Nuova asta" />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Nuova asta"
                    description="Configura crediti, rose e partecipanti"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nome asta</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            Password (opzionale)
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="Lascia vuoto per non proteggere l'asta"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        <div className="grid gap-2">
                            <Label htmlFor="budget">Crediti</Label>
                            <Input
                                id="budget"
                                type="number"
                                min={1}
                                value={data.budget_per_participant}
                                onChange={(e) =>
                                    setData(
                                        'budget_per_participant',
                                        Number(e.target.value),
                                    )
                                }
                            />
                            <InputError
                                message={errors.budget_per_participant}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slots_goalkeeper">Portieri</Label>
                            <Input
                                id="slots_goalkeeper"
                                type="number"
                                min={1}
                                value={data.slots_goalkeeper}
                                onChange={(e) =>
                                    setData(
                                        'slots_goalkeeper',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slots_defender">Difensori</Label>
                            <Input
                                id="slots_defender"
                                type="number"
                                min={1}
                                value={data.slots_defender}
                                onChange={(e) =>
                                    setData(
                                        'slots_defender',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slots_midfielder">
                                Centrocampisti
                            </Label>
                            <Input
                                id="slots_midfielder"
                                type="number"
                                min={1}
                                value={data.slots_midfielder}
                                onChange={(e) =>
                                    setData(
                                        'slots_midfielder',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slots_forward">Attaccanti</Label>
                            <Input
                                id="slots_forward"
                                type="number"
                                min={1}
                                value={data.slots_forward}
                                onChange={(e) =>
                                    setData(
                                        'slots_forward',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Label>Partecipanti</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addParticipant}
                            >
                                <Plus data-icon="inline-start" />
                                Aggiungi
                            </Button>
                        </div>

                        {data.participants.map((participant, index) => (
                            <div
                                key={index}
                                className="flex items-center gap-2"
                            >
                                <Input
                                    value={participant.name}
                                    onChange={(e) =>
                                        updateParticipant(index, {
                                            name: e.target.value,
                                        })
                                    }
                                    placeholder={`Partecipante ${index + 1}`}
                                    required
                                />
                                <label className="flex items-center gap-1.5 text-sm whitespace-nowrap">
                                    <input
                                        type="radio"
                                        name="is_agent"
                                        checked={participant.is_agent}
                                        onChange={() =>
                                            setData(
                                                'participants',
                                                data.participants.map(
                                                    (p, i) => ({
                                                        ...p,
                                                        is_agent: i === index,
                                                    }),
                                                ),
                                            )
                                        }
                                    />
                                    Gestito dall'IA
                                </label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeParticipant(index)}
                                    disabled={data.participants.length <= 2}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                        <InputError message={errors.participants} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Crea asta
                    </Button>
                </form>
            </div>
        </>
    );
}

AuctionsCreate.layout = {
    breadcrumbs: [
        { title: 'Aste', href: auctions.index() },
        { title: 'Nuova asta', href: auctionsCreate() },
    ],
};

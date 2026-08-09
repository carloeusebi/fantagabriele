import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronDown,
    History,
    Radio,
    Sparkles,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';
import auctions from '@/routes/auctions';

const STATUS_LINES = [
    { line: 'asta in tempo reale', tag: 'LIVE' },
    { line: 'consulente ia pronto a suggerire', tag: 'IA' },
    { line: 'budget e rose sincronizzati', tag: 'OK' },
];

const STEPS = [
    {
        number: '01',
        title: "Crea l'asta",
        description:
            'Imposta partecipanti, budget e ordine di chiamata prima di iniziare.',
    },
    {
        number: '02',
        title: 'Chiama i giocatori',
        description:
            "Metti all'asta un giocatore alla volta, in tempo reale per tutti i partecipanti.",
    },
    {
        number: '03',
        title: 'Assegna al miglior offerente',
        description:
            'Registra il prezzo finale: budget e rosa si aggiornano da soli.',
    },
];

const FEATURES = [
    {
        icon: Radio,
        title: 'Asta in tempo reale',
        description:
            'Ogni chiamata, offerta e assegnazione arriva subito a tutti i partecipanti collegati.',
    },
    {
        icon: Sparkles,
        title: 'Consulente IA',
        description:
            'Chiedi chi chiamare o quanto offrire: analizza quotazioni e crediti residui in un istante.',
    },
    {
        icon: Users,
        title: 'Partecipanti e budget',
        description:
            'Crediti residui e rosa per ruolo di ogni partecipante, sempre sotto controllo.',
    },
    {
        icon: History,
        title: 'Storico assegnazioni',
        description:
            'Ogni acquisto resta in archivio: chi ha preso cosa, e a quale prezzo.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="FantaGabrieleeee" />
            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border">
                    <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-2">
                            <img
                                src="/logo.png"
                                alt=""
                                className="size-8 object-contain"
                            />
                            <span className="font-semibold tracking-tight">
                                FantaGabrieleeee
                            </span>
                        </div>
                        {auth.user ? (
                            <Link
                                href={auctions.index()}
                                className="text-sm font-medium text-foreground hover:text-primary"
                            >
                                Vai alle aste
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="text-sm font-medium text-foreground hover:text-primary"
                            >
                                Accedi
                            </Link>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 md:grid-cols-2 md:py-24">
                        <div className="opacity-100 transition-all duration-700 motion-reduce:transition-none starting:translate-y-4 starting:opacity-0">
                            <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                                Asta fantacalcio · Stagione 2026/2027
                            </p>
                            <h1 className="mt-4 text-4xl leading-[1.05] font-bold tracking-tight sm:text-5xl">
                                La tua asta, gestita come un centro di comando.
                            </h1>
                            <p className="mt-5 max-w-md text-base text-muted-foreground">
                                Chiama i giocatori, assegna il miglior offerente
                                e lascia che il consulente IA ti suggerisca
                                quanto rilanciare. Tutto in tempo reale, senza
                                foglio Excel.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <Button asChild size="lg">
                                        <Link href={auctions.index()}>
                                            Vai alle aste
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button asChild size="lg">
                                            <Link href={login()}>
                                                Accedi
                                                <ArrowRight />
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            variant="ghost"
                                            size="lg"
                                        >
                                            <a href="#come-funziona">
                                                Come funziona
                                                <ChevronDown />
                                            </a>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="opacity-100 transition-all delay-150 duration-700 motion-reduce:transition-none starting:translate-y-4 starting:opacity-0">
                            <div className="relative mx-auto max-w-sm">
                                <div className="absolute -top-2 -left-2 size-6 border-t-2 border-l-2 border-primary" />
                                <div className="absolute -top-2 -right-2 size-6 border-t-2 border-r-2 border-primary" />
                                <div className="absolute -bottom-2 -left-2 size-6 border-b-2 border-l-2 border-primary" />
                                <div className="absolute -right-2 -bottom-2 size-6 border-r-2 border-b-2 border-primary" />

                                <div className="relative aspect-square overflow-hidden border-2 border-border bg-card shadow-lg">
                                    <span className="absolute top-3 left-3 z-10 bg-primary px-2 py-0.5 text-xs font-bold tracking-wider text-primary-foreground uppercase">
                                        In asta
                                    </span>
                                    <img
                                        src="/logo.png"
                                        alt="FantaGabrieleeee"
                                        className="size-full object-contain p-10"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="border-y-2 border-border bg-card">
                        <div className="mx-auto max-w-6xl px-6 py-6">
                            <div className="grid gap-3 font-mono text-sm sm:grid-cols-3">
                                {STATUS_LINES.map(({ line, tag }) => (
                                    <div
                                        key={tag}
                                        className="flex items-center gap-2"
                                    >
                                        <span className="text-primary">
                                            &gt;
                                        </span>
                                        <span className="text-muted-foreground">
                                            {line}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            className="ml-auto"
                                        >
                                            {tag}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="come-funziona"
                        className="mx-auto max-w-6xl px-6 py-20"
                    >
                        <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                            Come funziona
                        </p>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                            Dalla lista partecipanti alla rosa completa.
                        </h2>

                        <div className="mt-12 grid gap-10 sm:grid-cols-3">
                            {STEPS.map((step) => (
                                <div key={step.number}>
                                    <span className="text-5xl font-bold text-primary/25">
                                        {step.number}
                                    </span>
                                    <h3 className="mt-3 text-lg font-semibold">
                                        {step.title}
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {step.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="border-t-2 border-border bg-card">
                        <div className="mx-auto max-w-6xl px-6 py-20">
                            <p className="text-xs font-semibold tracking-[0.2em] text-primary uppercase">
                                Funzionalità
                            </p>
                            <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                                Tutto sotto controllo, durante l'asta.
                            </h2>

                            <div className="mt-12 grid gap-4 sm:grid-cols-2">
                                {FEATURES.map(
                                    ({ icon: Icon, title, description }) => (
                                        <div
                                            key={title}
                                            className="border-2 border-border bg-background p-6 shadow-lg transition-transform hover:-translate-y-0.5 motion-reduce:transition-none"
                                        >
                                            <Icon className="size-6 text-primary" />
                                            <h3 className="mt-4 font-semibold">
                                                {title}
                                            </h3>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {description}
                                            </p>
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-6 py-6 text-sm text-muted-foreground">
                        <div className="flex items-center gap-2">
                            <img
                                src="/logo.png"
                                alt=""
                                className="size-5 object-contain"
                            />
                            <span>FantaGabrieleeee — Stagione 2026/2027</span>
                        </div>
                        {auth.user ? (
                            <Link
                                href={auctions.index()}
                                className="font-medium text-foreground hover:text-primary"
                            >
                                Vai alle aste
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="font-medium text-foreground hover:text-primary"
                            >
                                Accedi
                            </Link>
                        )}
                    </div>
                </footer>
            </div>
        </>
    );
}

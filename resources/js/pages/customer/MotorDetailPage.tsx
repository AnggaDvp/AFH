import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

type FinancingOption = {
    downPayment: string;
    tenorCode: string;
    tenorLabel: string;
    installmentValue: string | null;
    isAvailable: boolean;
};

type Props = CustomerPageProps & {
    motor: {
        id: number;
        code: string;
        name: string;
        family: string;
        cashPrice: number;
    };
    brochure: {
        periodLabel: string;
        sourceName: string;
        notes: string | null;
    };
    financingOptions: FinancingOption[];
};

const tenorColumns = [
    { code: '11', label: '11' },
    { code: '17', label: '17' },
    { code: '24_23', label: '24 = 23' },
    { code: '30_28', label: '30 = 28' },
    { code: '36_32', label: '36 = 32' },
];

const currencyFormatter = new Intl.NumberFormat('id-ID');

function formatCashPrice(value: number): string {
    return `Rp ${currencyFormatter.format(value)}`;
}

function getInstallment(option?: FinancingOption): string {
    if (!option || !option.isAvailable || option.installmentValue === null) {
        return '-';
    }

    return option.installmentValue;
}

export default function MotorDetailPage(props: Props) {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');
    const financingRows = props.financingOptions.reduce<Record<string, Record<string, FinancingOption>>>(
        (rows, option) => {
            rows[option.downPayment] ??= {};
            rows[option.downPayment][option.tenorCode] = option;
            return rows;
        },
        {},
    );
    const downPayments = Object.keys(financingRows);
    const noteLines = props.brochure.notes?.split(/\r?\n/) ?? [];
    const requirementsTitle = 'Syarat Pengajuan pada Brosur';
    const sourceNotesTitle = 'Catatan dari Brosur';
    const requirementsIndex = noteLines.indexOf(requirementsTitle);
    const sourceNotesIndex = noteLines.indexOf(sourceNotesTitle);
    const requirements = requirementsIndex >= 0
        ? noteLines.slice(requirementsIndex + 1, sourceNotesIndex >= 0 ? sourceNotesIndex : undefined).filter(Boolean)
        : [];
    const sourceNotes = sourceNotesIndex >= 0 ? noteLines.slice(sourceNotesIndex + 1).filter(Boolean) : [];
    const startConversation = () => {
        if (processing) return;

        setError('');
        router.post('/customer/chat/start', {
            context_type: 'motor',
            motor_product_id: props.motor.id,
        }, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: () => setError('Kami belum dapat membuka chat. Silakan coba lagi.'),
        });
    };

    return (
        <CustomerLayout {...props}>
            <section className="mx-auto max-w-5xl">
                <Link href="/customer/motor" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline focus-visible:outline-none focus-visible:underline">
                    &larr; Kembali ke katalog
                </Link>

                <div className="customer-card mt-4 grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
                    <div className="flex min-h-48 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 p-6 sm:min-h-56">
                        <div className="text-center">
                            <p className="customer-eyebrow">Motor Honda</p>
                            <p className="mt-3 text-2xl font-bold text-slate-800">{props.motor.family}</p>
                            <p className="mt-2 text-sm text-slate-500">Informasi berdasarkan brosur</p>
                        </div>
                    </div>
                    <div className="min-w-0">
                    <span className="inline-flex rounded-full bg-brand-subtle px-3 py-1 text-xs font-semibold text-brand">
                        {props.motor.family}
                    </span>
                    <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{props.motor.name}</h1>
                    <p className="mt-5 text-sm font-semibold text-slate-500">Harga cash</p>
                    <p className="mt-1 text-2xl font-bold text-slate-950">{formatCashPrice(props.motor.cashPrice)}</p>
                    <p className="mt-3 text-sm text-slate-500">
                        Periode {props.brochure.periodLabel} · Sumber: {props.brochure.sourceName}
                    </p>
                    <button type="button" onClick={startConversation} disabled={processing} className="customer-button-primary mt-6 w-full sm:w-auto">
                        {processing ? 'Membuka chat...' : 'Saya Tertarik'}
                    </button>
                    {error && <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>}
                    </div>
                </div>

                <section aria-labelledby="financing-heading" className="mt-8">
                    <h2 id="financing-heading" className="text-2xl font-bold text-slate-950">Pilihan Kredit</h2>
                    {downPayments.length > 0 ? (
                        <>
                            <div className="customer-card mt-4 hidden overflow-x-auto md:block">
                                <table className="w-full min-w-[760px] border-collapse text-left text-sm">
                                    <thead className="bg-slate-50 text-slate-700">
                                        <tr>
                                            <th scope="col" className="px-4 py-4 font-semibold">DP</th>
                                            {tenorColumns.map((tenor) => (
                                                <th key={tenor.code} scope="col" className="px-4 py-4 font-semibold">{tenor.label}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {downPayments.map((downPayment) => (
                                            <tr key={downPayment}>
                                                <th scope="row" className="px-4 py-4 font-semibold text-slate-900">{downPayment}</th>
                                                {tenorColumns.map((tenor) => (
                                                    <td key={tenor.code} className="px-4 py-4 text-slate-700">
                                                        {getInstallment(financingRows[downPayment][tenor.code])}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-4 grid gap-3 md:hidden">
                                {downPayments.map((downPayment) => (
                                    <article key={downPayment} className="customer-card p-4">
                                        <h3 className="text-sm font-bold text-slate-950">DP {downPayment}</h3>
                                        <dl className="mt-3 grid grid-cols-2 gap-3">
                                            {tenorColumns.map((tenor) => (
                                                <div key={tenor.code} className="rounded-xl bg-slate-50 p-3">
                                                    <dt className="text-xs font-medium text-slate-500">Tenor {tenor.label}</dt>
                                                    <dd className="mt-1 text-sm font-semibold text-slate-900">
                                                        {getInstallment(financingRows[downPayment][tenor.code])}
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                    </article>
                                ))}
                            </div>
                        </>
                    ) : (
                        <div className="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
                            Data DP dan cicilan belum tersedia dari matriks brosur yang dapat diverifikasi. Nilai pembiayaan tidak ditampilkan sampai sumbernya tersedia.
                        </div>
                    )}
                </section>

                {requirements.length > 0 && (
                    <section aria-labelledby="brochure-requirements-heading" className="customer-card mt-8 p-5 sm:p-6">
                        <h2 id="brochure-requirements-heading" className="text-lg font-bold text-slate-950">
                            Syarat Pengajuan pada Brosur
                        </h2>
                        <ul className="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                            {requirements.map((requirement) => <li key={requirement}>{requirement}</li>)}
                        </ul>
                    </section>
                )}

                {sourceNotes.length > 0 && (
                    <section aria-labelledby="brochure-source-notes-heading" className="customer-card mt-4 p-5 sm:p-6">
                        <h2 id="brochure-source-notes-heading" className="text-lg font-bold text-slate-950">Catatan dari Brosur</h2>
                        <ul className="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                            {sourceNotes.map((note) => <li key={note}>{note}</li>)}
                        </ul>
                    </section>
                )}

                <div className="customer-card mt-8 p-5 sm:flex sm:flex-wrap sm:items-center sm:justify-between sm:gap-6">
                    <div>
                        <h2 className="font-bold text-slate-900">Saya Tertarik</h2>
                        <p className="mt-1 text-sm leading-6 text-slate-600">Mulai percakapan dengan tim kami untuk bertanya tentang motor ini.</p>
                    </div>
                    <button
                        type="button"
                        onClick={startConversation}
                        disabled={processing}
                        className="customer-button-primary mt-4 w-full sm:mt-0 sm:w-auto"
                    >
                        {processing ? 'Membuka chat...' : 'Saya Tertarik'}
                    </button>
                </div>
            </section>
        </CustomerLayout>
    );
}

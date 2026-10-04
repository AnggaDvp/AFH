import { Link } from '@inertiajs/react';
import CustomerEmptyState from '@/components/CustomerEmptyState';
import CustomerPageHeader from '@/components/CustomerPageHeader';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

type MotorListItem = {
    code: string;
    name: string;
    family: string;
    cashPrice: number;
    detailUrl: string;
};

type Props = CustomerPageProps & {
    brochure: {
        periodLabel: string;
        sourceName: string;
    } | null;
    search: string;
    selectedFamily: string;
    families: string[];
    motors: MotorListItem[];
};

const currencyFormatter = new Intl.NumberFormat('id-ID');

function formatCashPrice(value: number): string {
    return `Rp ${currencyFormatter.format(value)}`;
}

export default function MotorPage(props: Props) {
    return (
        <CustomerLayout {...props}>
            <section>
                <CustomerPageHeader eyebrow="Katalog" title="Motor Honda Baru" description="Temukan motor Honda sesuai kebutuhan Anda dan lihat rincian harga dari brosur." />

                {props.brochure && (
                    <p className="mt-3 text-sm text-slate-500">
                        Periode {props.brochure.periodLabel} · Sumber: {props.brochure.sourceName}
                    </p>
                )}

                <form
                    action="/customer/motor"
                    method="get"
                    className="customer-card mt-8 grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end sm:p-5"
                >
                    <label className="block">
                        <span className="mb-2 block text-sm font-semibold text-slate-700">Cari motor</span>
                        <input
                            type="search"
                            name="search"
                            defaultValue={props.search}
                            placeholder="Cari motor Honda..."
                            className="customer-input"
                        />
                    </label>
                    <label className="block">
                        <span className="mb-2 block text-sm font-semibold text-slate-700">Family</span>
                        <select
                            name="family"
                            defaultValue={props.selectedFamily}
                            className="customer-input"
                        >
                            <option value="">Semua</option>
                            {props.families.map((family) => (
                                <option key={family} value={family}>{family}</option>
                            ))}
                        </select>
                    </label>
                    <button
                        type="submit"
                        className="customer-button-primary"
                    >
                        Cari
                    </button>
                </form>

                <div className="mt-6 flex items-center justify-between gap-3">
                    <h2 className="text-lg font-bold text-slate-900">Daftar motor</h2>
                    <span className="text-sm text-slate-500">{props.motors.length} motor</span>
                </div>

                {props.motors.length > 0 ? (
                    <div aria-label="Daftar motor Honda" className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {props.motors.map((motor) => (
                            <article key={motor.code} className="customer-card flex min-w-0 flex-col p-5 transition-colors duration-300 hover:border-red-200">
                                <span className="w-fit rounded-full bg-brand-subtle px-3 py-1 text-xs font-semibold text-brand">
                                    {motor.family}
                                </span>
                                <h3 className="mt-4 text-lg font-bold leading-6 text-slate-950">{motor.name}</h3>
                                <p className="mt-4 text-xs font-medium uppercase tracking-wide text-slate-500">Harga cash</p>
                                <p className="mt-1 text-xl font-bold text-slate-900">{formatCashPrice(motor.cashPrice)}</p>
                                <Link
                                    href={motor.detailUrl}
                                    className="customer-button-secondary mt-5"
                                >
                                    Lihat Detail
                                </Link>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-4">
                        <CustomerEmptyState
                            title={props.brochure ? 'Motor tidak ditemukan' : 'Katalog belum tersedia'}
                            description={props.brochure ? 'Coba ubah kata pencarian atau pilih family yang lain.' : 'Belum ada data motor pada brosur aktif.'}
                            action={props.brochure && (props.search || props.selectedFamily) ? <Link href="/customer/motor" className="customer-button-secondary">Hapus filter</Link> : undefined}
                        />
                    </div>
                )}
            </section>
        </CustomerLayout>
    );
}

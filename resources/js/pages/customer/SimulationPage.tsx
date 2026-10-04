import { Link } from '@inertiajs/react';
import CustomerEmptyState from '@/components/CustomerEmptyState';
import CustomerPageHeader from '@/components/CustomerPageHeader';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

const steps = ['Pilih Motor', 'Pilih DP', 'Pilih Tenor', 'Lihat Hasil'];

export default function SimulationPage(props: CustomerPageProps) {
    return (
        <CustomerLayout {...props}>
            <section className="mx-auto max-w-4xl">
                <CustomerPageHeader eyebrow="Layanan" title="Simulasi Kredit" description="Kenali tahapan simulasi kredit motor Honda. Perhitungan DP dan cicilan belum tersedia." />
                <ol className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Tahapan simulasi kredit">
                    {steps.map((step, index) => (
                        <li key={step} className="customer-card flex items-center gap-3 p-4">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-subtle text-sm font-bold text-brand">{index + 1}</span>
                            <span className="text-sm font-semibold text-slate-800">{step}</span>
                        </li>
                    ))}
                </ol>
                <div className="mt-6">
                    <CustomerEmptyState
                        title="Perhitungan simulasi belum tersedia"
                        description="Nilai DP dan cicilan belum ditampilkan sampai data pembiayaan yang dapat diverifikasi tersedia. Anda tetap bisa melihat motor dan harga cash pada katalog."
                        action={<Link href="/customer/motor" className="customer-button-secondary">Jelajahi motor Honda</Link>}
                    />
                </div>
            </section>
        </CustomerLayout>
    );
}

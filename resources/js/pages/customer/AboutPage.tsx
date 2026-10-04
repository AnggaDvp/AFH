import CustomerPageHeader from '@/components/CustomerPageHeader';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

const services = [
    'Motor Honda Baru',
    'Kredit Motor',
    'Dana Tunai / BPKB',
    'Simulasi',
];

export default function AboutPage(props: CustomerPageProps) {
    return (
        <CustomerLayout {...props}>
            <section className="grid items-start gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12">
                <div className="pt-2">
                    <CustomerPageHeader eyebrow="Tentang Kami" title="Tentang Agency Finance Honda" description="Informasi layanan motor Honda baru, kredit motor, dana tunai dengan jaminan BPKB, dan konsultasi dalam satu tempat." />
                </div>

                <div className="customer-card p-6 sm:p-8">
                    <div className="flex items-center gap-4">
                        <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white p-2">
                            <img src="/assets/images/logo.jpeg" alt="Logo Agency Finance Honda" className="h-full w-full object-contain" />
                        </span>
                        <div>
                            <h2 className="font-bold text-slate-950">Layanan yang tersedia</h2>
                            <p className="mt-1 text-sm text-slate-500">Informasi layanan Agency Finance Honda</p>
                        </div>
                    </div>
                    <ul className="mt-6 grid gap-3 sm:grid-cols-2">
                        {services.map((service) => (
                            <li key={service} className="flex items-center gap-2.5 text-sm text-slate-700">
                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                    <svg aria-hidden="true" viewBox="0 0 16 16" className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="m3 8 3 3 7-7" />
                                    </svg>
                                </span>
                                {service}
                            </li>
                        ))}
                    </ul>
                </div>
            </section>
        </CustomerLayout>
    );
}

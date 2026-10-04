import { Link } from '@inertiajs/react';
import CustomerEmptyState from '@/components/CustomerEmptyState';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

const services = [
    {
        title: 'Motor Honda Baru',
        description: 'Jelajahi katalog motor Honda, harga cash, dan informasi dari brosur yang tersedia.',
        href: '/customer/motor',
        action: 'Lihat motor',
    },
    {
        title: 'Dana Tunai / BPKB',
        description: 'Isi informasi awal kendaraan untuk melanjutkan konsultasi melalui chat.',
        href: '/customer/dana-tunai',
        action: 'Ajukan dana tunai',
    },
    {
        title: 'Simulasi Kredit',
        description: 'Pelajari informasi simulasi kredit yang tersedia saat ini.',
        href: '/customer/simulasi',
        action: 'Lihat informasi',
    },
];

export default function CustomerHome(props: CustomerPageProps) {
    return (
        <CustomerLayout {...props}>
            <section>
                <div className="customer-card border-l-4 border-l-brand px-6 py-8 sm:px-9 sm:py-10">
                    <p className="customer-eyebrow">Beranda customer</p>
                    <h1 className="mt-3 break-words text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Halo, {props.customerName}</h1>
                    <p className="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                        Selamat datang di Agency Finance Honda. Pilih layanan yang ingin Anda lihat.
                    </p>
                </div>

                <div className="mt-10">
                    <div className="mb-5">
                        <h2 className="text-xl font-bold text-slate-950 sm:text-2xl">Layanan untuk Anda</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Jelajahi halaman layanan Agency Finance Honda.
                        </p>
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
                        {services.map((service, index) => (
                            <article key={service.href} className="customer-card flex min-w-0 flex-col p-5 transition-colors duration-300 hover:border-red-200 sm:p-6">
                                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-subtle text-brand">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                        {index === 0 ? <path d="M4 15.5 6.5 9h11l2.5 6.5M3 15.5h18v3H3zM7 18.5v2m10-2v2M7.5 12h9" /> : index === 1 ? <path d="M4 6h16v12H4zM8 10h8m-8 4h5" /> : <path d="M5 4h14v16H5zM8 9h8m-8 4h8m-8 4h4" />}
                                    </svg>
                                </span>
                                <h3 className="mt-4 text-lg font-bold text-slate-950">{service.title}</h3>
                                <p className="mt-2 flex-1 text-sm leading-6 text-slate-600">{service.description}</p>
                                <Link
                                    href={service.href}
                                    className="mt-5 inline-flex min-h-11 items-center self-start text-sm font-semibold text-brand hover:text-brand-dark focus-visible:outline-none focus-visible:underline"
                                >
                                    {service.action} <span aria-hidden="true" className="ml-2">&rarr;</span>
                                </Link>
                            </article>
                        ))}
                    </div>
                </div>
                <section className="mt-10" aria-labelledby="activity-title">
                    <h2 id="activity-title" className="mb-4 text-xl font-bold text-slate-950">Percakapan Anda</h2>
                    <CustomerEmptyState
                        title="Lanjutkan konsultasi"
                        description="Lihat percakapan layanan dan balasan terbaru langsung di halaman Chat."
                        action={<Link href="/customer/chat" className="customer-button-secondary">Buka chat</Link>}
                    />
                </section>
            </section>
        </CustomerLayout>
    );
}

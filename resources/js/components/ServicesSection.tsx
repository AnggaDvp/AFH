type Props = {
    onRequestAccess: () => void;
};

const services = [
    {
        title: 'Jual Motor Baru Cash atau Kredit',
        description: 'Customer dapat memilih dan mengajukan pembelian motor Honda baru secara cash maupun kredit.',
        action: 'Lihat Motor',
        icon: (
            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="5.5" cy="17.5" r="3.5" /><circle cx="18.5" cy="17.5" r="3.5" />
                <path d="M15 6h2l3 7h-5l-3-5H8l-4 7m5-7 3 9" />
            </svg>
        ),
    },
    {
        title: 'Gadai BPKB Motor di Sini',
        description: 'Layanan dana tunai dengan jaminan BPKB motor sesuai ketentuan pengajuan.',
        action: 'Ajukan Dana Tunai',
        icon: (
            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <rect x="3" y="5" width="18" height="14" rx="2" /><path d="M7 9h.01M17 15h.01M12 9v6m-2-2 2 2 2-2" />
            </svg>
        ),
    },
    {
        title: 'Simulasi Kredit',
        description: 'Lihat tahapan simulasi kredit. Perhitungan cicilan belum tersedia sampai data pembiayaan dapat diverifikasi.',
        action: 'Lihat Simulasi',
        icon: (
            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <rect x="5" y="2.5" width="14" height="19" rx="2" /><path d="M8 6h8M8 10h2m4 0h2m-8 4h2m4 0h2m-8 4h2m4 0h2" />
            </svg>
        ),
    },
];

export default function ServicesSection({ onRequestAccess }: Props) {
    return (
        <section id="layanan" className="scroll-mt-8 bg-white py-16 sm:py-20 lg:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Layanan Kami</p>
                    <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Pilih Layanan yang Anda Butuhkan</h2>
                    <p className="mt-4 text-base leading-7 text-slate-600 sm:text-lg">Informasi layanan motor Honda dan dana tunai dapat dimulai dari sini.</p>
                </div>
                <div className="mt-10 grid gap-5 md:grid-cols-3 lg:mt-14">
                    {services.map((service) => (
                        <article key={service.title} className="customer-card group flex h-full flex-col p-6 transition-colors duration-300 hover:border-red-200 sm:p-7">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-700 transition duration-300 group-hover:bg-red-600 group-hover:text-white">
                                {service.icon}
                            </div>
                            <h3 className="mt-5 text-xl font-bold leading-7 text-slate-950">{service.title}</h3>
                            <p className="mt-3 flex-1 text-sm leading-6 text-slate-600">{service.description}</p>
                            <button
                                type="button"
                                onClick={onRequestAccess}
                                className="customer-button-secondary mt-6 gap-2 self-start"
                            >
                                {service.action}
                                <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M3 10h14m-6-6 6 6-6 6" />
                                </svg>
                            </button>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

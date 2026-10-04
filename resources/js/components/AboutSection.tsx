const services = [
    'Motor Honda Baru',
    'Kredit Motor',
    'Dana Tunai / BPKB',
    'Simulasi',
];

export default function AboutSection() {
    return (
        <section id="tentang-kami" className="scroll-mt-8 bg-white py-16 sm:py-20 lg:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Tentang Kami</p>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Tentang Agency Finance Honda</h2>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-slate-600">
                            Website ini membantu customer mendapatkan informasi motor Honda baru, dana tunai dengan jaminan BPKB, tahapan simulasi, dan konsultasi melalui chat.
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-white p-2">
                                <img src="/assets/images/logo.jpeg" alt="Logo Agency Finance Honda" className="h-full w-full object-contain" />
                            </div>
                            <div>
                                <h3 className="font-bold text-slate-950">Layanan yang dapat diakses</h3>
                                <p className="mt-1 text-sm text-slate-500">Mulai melalui Customer Gate</p>
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
                </div>
            </div>
        </section>
    );
}

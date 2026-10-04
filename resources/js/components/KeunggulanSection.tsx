const items = [
    {
        title: 'Proses Mudah',
        description:
            'Alur pengajuan yang sederhana dan tidak berbelit, memudahkan Anda setiap langkah.',
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="26"
                height="26"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M20 6L9 17l-5-5" />
            </svg>
        ),
    },
    {
        title: 'Informasi Transparan',
        description:
            'Semua biaya dan ketentuan dijelaskan secara jelas sejak awal, tanpa biaya tersembunyi.',
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="26"
                height="26"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        ),
    },
    {
        title: 'Pilihan Pembiayaan',
        description:
            'Pilihan skema pembiayaan yang fleksibel, menyesuaikan dengan kemampuan dan kebutuhan Anda.',
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="26"
                height="26"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
        ),
    },
    {
        title: 'Dukungan Marketing',
        description:
            'Tim marketing profesional siap membantu dan memberikan konsultasi terbaik untuk kebutuhan Anda.',
        icon: (
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="26"
                height="26"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
        ),
    },
];

export default function KeunggulanSection() {
    return (
        <section id="keunggulan" className="py-16 sm:py-20 lg:py-24 bg-white">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-2xl text-center">
                    <span className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        Kenapa Kami
                    </span>
                    <h2 className="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Keunggulan NSC Finance
                    </h2>
                    <p className="mt-4 text-lg text-gray-600">
                        Komitmen kami memberikan pengalaman pembiayaan yang terbaik untuk Anda.
                    </p>
                </div>

                <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item, idx) => (
                        <div
                            key={item.title}
                            className="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-7 shadow-sm hover:shadow-md hover:border-gray-300 transition-all"
                        >
                            <div className="absolute top-0 right-0 h-20 w-20 rounded-bl-full opacity-[0.04]"
                                style={{
                                    backgroundColor: idx % 2 === 0 ? '#dc2626' : '#111827',
                                }}
                            />
                            <div className="relative">
                                <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors">
                                    {item.icon}
                                </div>
                                <h3 className="mt-5 text-lg font-bold text-gray-900">
                                    {item.title}
                                </h3>
                                <p className="mt-3 text-sm text-gray-600 leading-6">
                                    {item.description}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

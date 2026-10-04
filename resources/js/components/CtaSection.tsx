type Props = {
    onRequestAccess: () => void;
};

export default function CtaSection({ onRequestAccess }: Props) {
    return (
        <section className="bg-slate-50 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div className="mx-auto flex max-w-7xl flex-col items-start justify-between gap-7 rounded-3xl bg-slate-950 px-6 py-9 text-white sm:px-10 sm:py-12 lg:flex-row lg:items-center">
                <div className="max-w-2xl">
                    <p className="text-sm font-semibold text-red-300">Agency Finance Honda</p>
                    <h2 className="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Mulai dari informasi yang Anda butuhkan</h2>
                    <p className="mt-3 text-sm leading-6 text-slate-300 sm:text-base">
                        Lanjutkan melalui Customer Gate untuk mengakses informasi layanan.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onRequestAccess}
                    className="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-base font-semibold text-white transition duration-300 hover:bg-red-500 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-300 lg:w-auto"
                >
                    Mulai Sekarang
                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 10h14m-6-6 6 6-6 6" />
                    </svg>
                </button>
            </div>
        </section>
    );
}

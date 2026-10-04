type Props = {
    onRequestAccess: () => void;
};

export default function HeroSection({ onRequestAccess }: Props) {
    return (
        <section className="border-b border-slate-100 bg-white">
            <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                <div className="text-center lg:text-left">
                    <div className="inline-flex items-center gap-2 rounded-full border border-red-100 bg-brand-subtle px-3.5 py-1.5 text-xs font-semibold text-brand">
                        <span className="h-2 w-2 rounded-full bg-red-600" />
                        Agency Finance Honda
                    </div>
                    <h1 className="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-950 sm:text-5xl lg:text-[3.5rem]">
                        Solusi Kredit Motor Honda &amp; Dana Tunai untuk Kebutuhan Anda
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg lg:mx-0">
                        Beli motor Honda baru secara cash atau kredit, ajukan dana tunai dengan jaminan BPKB motor, dan dapatkan informasi simulasi serta pengajuan dalam satu tempat.
                    </p>
                    <div className="mt-9 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                        <button
                            type="button"
                            onClick={onRequestAccess}
                            className="customer-button-primary gap-2 px-7 text-base"
                        >
                            Mulai Sekarang
                            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M5 12h14M13 5l7 7-7 7" />
                            </svg>
                        </button>
                        <a
                            href="#layanan"
                            className="customer-button-secondary px-7 text-base"
                        >
                            Kenali Layanan
                        </a>
                    </div>
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm text-slate-600 lg:justify-start">
                        <span className="inline-flex items-center gap-2">
                            <span className="h-1.5 w-1.5 rounded-full bg-red-600" />
                            Motor Honda baru
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <span className="h-1.5 w-1.5 rounded-full bg-red-600" />
                            Dana tunai BPKB
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <span className="h-1.5 w-1.5 rounded-full bg-red-600" />
                            Informasi simulasi
                        </span>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-lg lg:max-w-none">
                    <div className="customer-card relative overflow-hidden border-t-4 border-t-brand p-7 sm:p-10">
                        <div className="relative flex items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-brand">Layanan untuk kebutuhan Anda</p>
                                <h2 className="mt-2 text-2xl font-bold text-slate-950 sm:text-3xl">Satu langkah untuk mulai</h2>
                            </div>
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-sm">
                                <img src="/assets/images/logo.jpeg" alt="Logo Agency Finance Honda" className="h-full w-full object-contain" />
                            </div>
                        </div>
                        <div className="relative mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            {[
                                ['Motor Honda', 'Cash atau kredit'],
                                ['Dana Tunai', 'Jaminan BPKB'],
                                ['Simulasi', 'Gambaran cicilan'],
                            ].map(([title, description]) => (
                                <div key={title} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p className="font-semibold text-slate-900">{title}</p>
                                    <p className="mt-1 text-sm text-slate-600">{description}</p>
                                </div>
                            ))}
                        </div>
                        <p className="relative mt-7 text-sm leading-6 text-slate-600">
                            Pilih layanan dan lengkapi identifikasi awal untuk melihat informasi berikutnya.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

type Props = {
    onRequestAccess: () => void;
};

export default function SimulationSection({ onRequestAccess }: Props) {
    return (
        <section id="simulasi" className="scroll-mt-8 bg-slate-50 py-16 sm:py-20 lg:py-24">
            <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Simulasi</p>
                    <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Simulasi Kredit</h2>
                    <h3 className="mt-5 text-xl font-semibold text-slate-800">Kenali Tahapan Simulasi Kredit</h3>
                    <p className="mt-4 max-w-xl text-base leading-7 text-slate-600">
                        Pelajari tahapan simulasi kredit. Perhitungan cicilan belum tersedia sampai data pembiayaan dapat diverifikasi.
                    </p>
                    <button
                        type="button"
                        onClick={onRequestAccess}
                        className="customer-button-primary mt-8 gap-2 text-base"
                    >
                        Lihat Informasi Simulasi
                        <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 10h14m-6-6 6 6-6 6" />
                        </svg>
                    </button>
                </div>
                <div className="customer-card p-6 sm:p-9">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-700">
                            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="5" y="2.5" width="14" height="19" rx="2" /><path d="M8 6h8M8 10h2m4 0h2m-8 4h2m4 0h2" />
                            </svg>
                        </div>
                        <div>
                            <p className="font-semibold text-slate-950">Simulasi kredit</p>
                            <p className="text-sm text-slate-500">Tahapan layanan</p>
                        </div>
                    </div>
                    <ol className="mt-6 grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                        {['Pilih motor', 'Pilih DP', 'Pilih tenor', 'Lihat hasil'].map((step, index) => (
                            <li key={step} className="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <span className="font-bold text-brand">{index + 1}</span>{step}
                            </li>
                        ))}
                    </ol>
                    <p className="mt-5 text-xs leading-5 text-slate-500">Belum ada perhitungan finansial pada halaman ini.</p>
                </div>
            </div>
        </section>
    );
}

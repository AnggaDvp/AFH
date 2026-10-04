type Props = {
    onRequestAccess: () => void;
};

export default function MotorSection({ onRequestAccess }: Props) {
    return (
        <section id="motor-baru" className="scroll-mt-8 bg-slate-50 py-16 sm:py-20 lg:py-24">
            <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div className="order-2 lg:order-1">
                    <div className="customer-card flex min-h-64 items-center justify-center p-6 sm:min-h-80">
                        <div className="w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 p-6">
                            <div className="flex items-center justify-between">
                                <span className="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Motor Honda Baru</span>
                                <svg aria-hidden="true" viewBox="0 0 24 24" className="h-8 w-8 text-red-700" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="5.5" cy="17.5" r="3.5" /><circle cx="18.5" cy="17.5" r="3.5" />
                                    <path d="M15 6h2l3 7h-5l-3-5H8l-4 7m5-7 3 9" />
                                </svg>
                            </div>
                            <p className="mt-7 text-sm leading-6 text-slate-600">Lihat katalog dan rincian harga motor Honda dari brosur yang tersedia.</p>
                            <div className="mt-8 grid grid-cols-2 gap-3">
                                <div className="rounded-xl border border-slate-200 bg-white p-4">
                                    <p className="text-xs text-slate-500">Pembelian</p>
                                    <p className="mt-1 font-semibold text-slate-900">Cash</p>
                                </div>
                                <div className="rounded-xl border border-slate-200 bg-white p-4">
                                    <p className="text-xs text-slate-500">Pembelian</p>
                                    <p className="mt-1 font-semibold text-slate-900">Kredit</p>
                                </div>
                            </div>
                            <p className="mt-5 text-xs leading-5 text-slate-500">Informasi pembiayaan ditampilkan jika data brosur tersedia.</p>
                        </div>
                    </div>
                </div>
                <div className="order-1 lg:order-2">
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Motor Honda Baru</p>
                    <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Jual Motor Baru Cash atau Kredit</h2>
                    <h3 className="mt-5 text-xl font-semibold text-slate-800">Pilih Motor Honda Baru Sesuai Kebutuhanmu</h3>
                    <p className="mt-4 text-base leading-7 text-slate-600">
                        Temukan informasi motor Honda baru, harga cash, dan rincian dari brosur yang tersedia.
                    </p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <span className="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700">Pembelian cash</span>
                        <span className="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700">Pembelian kredit</span>
                    </div>
                    <button
                        type="button"
                        onClick={onRequestAccess}
                        className="customer-button-primary mt-8 gap-2 text-base"
                    >
                        Lihat Pilihan Motor
                        <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 10h14m-6-6 6 6-6 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>
    );
}

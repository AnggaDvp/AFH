type Props = {
    onRequestAccess: () => void;
};

export default function BpkbSection({ onRequestAccess }: Props) {
    return (
        <section id="dana-tunai" className="scroll-mt-8 bg-white py-16 sm:py-20 lg:py-24">
            <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Dana Tunai</p>
                    <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Gadai BPKB Motor di Sini</h2>
                    <h3 className="mt-5 text-xl font-semibold text-slate-800">Butuh Dana Tunai? Manfaatkan BPKB Motor Anda</h3>
                    <p className="mt-4 max-w-xl text-base leading-7 text-slate-600">
                        Customer dapat mengajukan dana tunai dengan jaminan BPKB motor dan mendapatkan informasi mengenai proses pengajuan setelah berhasil melakukan identifikasi.
                    </p>
                    <button
                        type="button"
                        onClick={onRequestAccess}
                        className="mt-8 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-base font-semibold text-white transition duration-300 hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-200"
                    >
                        Ajukan Dana Tunai
                        <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 10h14m-6-6 6 6-6 6" />
                        </svg>
                    </button>
                </div>
                <div className="rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:p-9">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-red-700 shadow-sm">
                        <svg aria-hidden="true" viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M6 3h9l4 4v14H6z" /><path d="M14 3v5h5M9 13h7m-7 4h7" />
                        </svg>
                    </div>
                    <h3 className="mt-5 text-lg font-bold text-slate-950">Informasi pengajuan setelah identifikasi</h3>
                    <p className="mt-2 text-sm leading-6 text-slate-600">
                        Lengkapi Customer Gate untuk melanjutkan ke informasi terkait pengajuan dana tunai dengan jaminan BPKB motor.
                    </p>
                    <div className="mt-6 h-px bg-slate-200" />
                    <p className="mt-5 text-xs leading-5 text-slate-500">
                        Ketersediaan dan ketentuan pengajuan akan diinformasikan pada tahap berikutnya.
                    </p>
                </div>
            </div>
        </section>
    );
}

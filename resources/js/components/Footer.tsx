type Props = {
    onRequestAccess: () => void;
};

const links = [
    ['Layanan', '#layanan'],
    ['Motor Honda Baru', '#motor-baru'],
    ['Dana Tunai / BPKB', '#dana-tunai'],
    ['Simulasi Kredit', '#simulasi'],
    ['Tentang Kami', '#tentang-kami'],
];

export default function Footer({ onRequestAccess }: Props) {
    return (
        <footer className="border-t border-slate-200 bg-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-9 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white p-1.5">
                        <img src="/assets/images/logo.jpeg" alt="Logo Agency Finance Honda" className="h-full w-full object-contain" />
                    </div>
                    <div>
                        <p className="font-bold text-slate-950">Agency Finance Honda</p>
                        <p className="text-xs text-slate-500">Informasi layanan motor dan pembiayaan</p>
                    </div>
                </div>
                <nav aria-label="Navigasi footer" className="flex flex-wrap gap-x-5 gap-y-3">
                    {links.map(([label, href]) => (
                        <a key={href} href={href} className="text-sm text-slate-600 transition duration-300 hover:text-red-700">
                            {label}
                        </a>
                    ))}
                </nav>
                <button
                    type="button"
                    onClick={onRequestAccess}
                    className="inline-flex min-h-11 items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition duration-300 hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-200"
                >
                    Mulai Sekarang
                </button>
            </div>
            <div className="border-t border-slate-100 px-4 py-4 text-center text-xs text-slate-500">
                © {new Date().getFullYear()} Agency Finance Honda. Informasi layanan.
            </div>
        </footer>
    );
}

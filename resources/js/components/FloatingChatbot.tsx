type Props = {
    onRequestGate: () => void;
};

export default function FloatingChatbot({ onRequestGate }: Props) {
    return (
        <div className="fixed bottom-4 right-4 z-40 sm:bottom-6 sm:right-6">
            <button
                type="button"
                onClick={onRequestGate}
                aria-label="Mulai melalui Customer Gate"
                className="group flex items-center gap-3 rounded-full border border-slate-200 bg-white p-1.5 pr-2.5 shadow-lg transition duration-300 hover:-translate-y-0.5 hover:shadow-xl focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-200 sm:pr-4 sm:py-2 sm:pl-2"
            >
                <span className="relative block h-11 w-11 overflow-hidden rounded-full ring-2 ring-red-600 sm:h-12 sm:w-12">
                    <img src="/assets/images/affiyah.png" alt="" className="h-full w-full rounded-full object-cover" />
                </span>
                <span className="hidden text-left sm:block">
                    <span className="block text-sm font-bold text-slate-950">Affiyah</span>
                    <span className="block text-xs text-slate-500">Mulai di sini</span>
                </span>
                <svg aria-hidden="true" viewBox="0 0 20 20" className="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M3 10h14m-6-6 6 6-6 6" />
                </svg>
            </button>
        </div>
    );
}

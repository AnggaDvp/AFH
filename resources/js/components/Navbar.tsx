import { useState, useEffect } from 'react';

const navLinks = [
    { label: 'Beranda', href: '#beranda' },
    { label: 'Kredit Motor', href: '#kredit-motor' },
    { label: 'Dana Tunai', href: '#dana-tunai' },
    { label: 'Simulasi', href: '#simulasi' },
    { label: 'Tentang Kami', href: '#tentang-kami' },
];

export default function Navbar() {
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const scrollTo = (href: string) => {
        setOpen(false);
        const el = document.querySelector(href);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const handleCta = () => {
        setOpen(false);
        const el = document.querySelector('#lengkapi-data-diri');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    return (
        <header
            className={`sticky top-0 z-50 w-full transition-all duration-300 ${
                scrolled
                    ? 'bg-white shadow-sm border-b border-gray-100'
                    : 'bg-white/90 backdrop-blur-sm'
            }`}
        >
            <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a
                    href="#beranda"
                    onClick={(e) => {
                        e.preventDefault();
                        scrollTo('#beranda');
                    }}
                    className="flex items-center gap-3"
                >
                    <div className="h-10 w-10 overflow-hidden rounded-xl bg-white ring-1 ring-gray-200 shadow-sm flex items-center justify-center p-1">
                        <img
                            src="/assets/images/logo.jpeg"
                            alt="NSC Finance"
                            className="h-full w-full object-contain"
                        />
                    </div>
                    <span className="text-lg font-bold tracking-tight text-gray-900">
                        NSC <span className="text-red-600">Finance</span>
                    </span>
                </a>

                <nav className="hidden md:flex items-center gap-8">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            onClick={(e) => {
                                e.preventDefault();
                                scrollTo(link.href);
                            }}
                            className="text-sm font-medium text-gray-700 hover:text-red-600 transition-colors"
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>

                <div className="hidden md:block">
                    <button
                        onClick={handleCta}
                        className="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors"
                    >
                        Lengkapi Data Diri
                    </button>
                </div>

                <button
                    onClick={() => setOpen((v) => !v)}
                    className="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100"
                    aria-label="Toggle menu"
                    aria-expanded={open}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        {open ? (
                            <>
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </>
                        ) : (
                            <>
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <line x1="3" y1="12" x2="21" y2="12" />
                                <line x1="3" y1="18" x2="21" y2="18" />
                            </>
                        )}
                    </svg>
                </button>
            </div>

            {open && (
                <div className="md:hidden border-t border-gray-100 bg-white">
                    <div className="space-y-1 px-4 py-3 sm:px-6">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                onClick={(e) => {
                                    e.preventDefault();
                                    scrollTo(link.href);
                                }}
                                className="block rounded-lg px-3 py-2.5 text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-red-600"
                            >
                                {link.label}
                            </a>
                        ))}
                        <button
                            onClick={handleCta}
                            className="mt-2 w-full inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors"
                        >
                            Lengkapi Data Diri
                        </button>
                    </div>
                </div>
            )}
        </header>
    );
}

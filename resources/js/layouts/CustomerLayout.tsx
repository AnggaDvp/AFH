import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import AppLayout from '@/layouts/AppLayout';
import type { CustomerPageProps } from '@/types/customer';

const navigation = [
    { label: 'Beranda', href: '/customer' },
    { label: 'Motor Honda', href: '/customer/motor' },
    { label: 'Dana Tunai', href: '/customer/dana-tunai' },
    { label: 'Simulasi', href: '/customer/simulasi' },
    { label: 'Tentang Kami', href: '/customer/tentang' },
    { label: 'Chat', href: '/customer/chat' },
];

type Props = CustomerPageProps & {
    children: ReactNode;
};

export default function CustomerLayout({ children, customerName, customerPhone }: Props) {
    const { url } = usePage();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [profileMenuOpen, setProfileMenuOpen] = useState(false);
    const [logoutProcessing, setLogoutProcessing] = useState(false);
    const [logoutError, setLogoutError] = useState('');
    const profileRef = useRef<HTMLDivElement>(null);
    const menuButtonRef = useRef<HTMLButtonElement>(null);
    const currentPath = url.split('?')[0].replace(/\/$/, '') || '/';

    const isActive = (href: string) => currentPath === href || (href !== '/customer' && currentPath.startsWith(`${href}/`));

    useEffect(() => {
        const dismiss = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') return;
            if (mobileMenuOpen) menuButtonRef.current?.focus();
            setMobileMenuOpen(false);
            setProfileMenuOpen(false);
        };
        const outside = (event: PointerEvent) => {
            if (profileRef.current && !profileRef.current.contains(event.target as Node)) setProfileMenuOpen(false);
        };
        document.addEventListener('keydown', dismiss);
        document.addEventListener('pointerdown', outside);
        return () => {
            document.removeEventListener('keydown', dismiss);
            document.removeEventListener('pointerdown', outside);
        };
    }, [mobileMenuOpen]);

    const handleLogout = () => {
        if (logoutProcessing) return;

        setLogoutError('');
        router.post('/customer/logout', {}, {
            onStart: () => setLogoutProcessing(true),
            onFinish: () => setLogoutProcessing(false),
            onError: () => setLogoutError('Kami belum dapat mengakhiri sesi. Silakan coba lagi.'),
        });
    };

    const renderNavigation = (mobile = false) => (
        <nav aria-label={mobile ? 'Navigasi customer mobile' : 'Navigasi customer'} className={mobile ? 'grid gap-1' : 'flex items-center gap-0.5'}>
            {navigation.map((item) => {
                const active = isActive(item.href);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        aria-current={active ? 'page' : undefined}
                        onClick={() => {
                            setMobileMenuOpen(false);
                            setProfileMenuOpen(false);
                        }}
                        className={`rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand ${
                            active
                                ? 'bg-brand-subtle text-brand'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-red-700'
                        } ${mobile ? 'block' : 'whitespace-nowrap'}`}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );

    const renderLogoutButton = () => (
        <button
            type="button"
            onClick={handleLogout}
            disabled={logoutProcessing}
            className="inline-flex min-h-10 w-full items-center justify-center rounded-xl px-3 text-sm font-semibold text-red-700 transition-colors duration-200 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
        >
            {logoutProcessing ? 'Memproses...' : 'Logout'}
        </button>
    );

    return (
        <AppLayout>
            <div className="min-h-screen min-w-0 bg-slate-50 text-slate-900">
                <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <Link href="/customer" className="flex min-w-0 items-center gap-3">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5">
                                <img
                                    src="/assets/images/logo.jpeg"
                                    alt="Logo Agency Finance Honda"
                                    className="h-full w-full object-contain"
                                />
                            </span>
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-bold tracking-tight text-slate-950 sm:text-base">
                                    Agency Finance Honda
                                </span>
                                <span className="hidden text-xs text-slate-500 sm:block">Layanan customer</span>
                            </span>
                        </Link>

                        <div className="hidden xl:block">{renderNavigation()}</div>

                        <div className="hidden shrink-0 items-center gap-2 xl:flex">
                            <div ref={profileRef} className="relative">
                                <button
                                    type="button"
                                    aria-expanded={profileMenuOpen}
                                    aria-controls="customer-profile-menu"
                                    onClick={() => setProfileMenuOpen((open) => !open)}
                                    className="flex min-h-11 max-w-48 items-center gap-2 rounded-xl px-3 text-left transition-colors duration-200 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-100"
                                >
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">
                                        {customerName.slice(0, 1).toUpperCase()}
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-800">{customerName}</span>
                                        <span className="block truncate text-xs text-slate-500">{customerPhone}</span>
                                    </span>
                                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8">
                                        <path d="m5 7 5 5 5-5" />
                                    </svg>
                                </button>

                                {profileMenuOpen && (
                                    <div id="customer-profile-menu" className="absolute right-0 top-full mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-soft">
                                        <div className="border-b border-slate-100 px-3 pb-3">
                                            <p className="truncate text-sm font-semibold text-slate-900">{customerName}</p>
                                            <p className="mt-1 text-sm text-slate-500">{customerPhone}</p>
                                        </div>
                                    <div className="pt-2">{renderLogoutButton()}</div>
                                    </div>
                                )}
                            </div>
                        </div>

                        <button
                            ref={menuButtonRef}
                            type="button"
                            aria-label={mobileMenuOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'}
                            aria-expanded={mobileMenuOpen}
                            aria-controls="customer-mobile-menu"
                            onClick={() => setMobileMenuOpen((open) => !open)}
                            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-100 xl:hidden"
                        >
                            {mobileMenuOpen ? (
                                <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                    <path d="m18 6-12 12M6 6l12 12" />
                                </svg>
                            ) : (
                                <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                    <path d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            )}
                        </button>
                    </div>

                    {mobileMenuOpen && (
                        <div id="customer-mobile-menu" className="absolute inset-x-0 top-full max-h-[calc(100dvh-4rem)] overflow-y-auto border-b border-slate-200 bg-white px-4 py-4 shadow-soft xl:hidden sm:px-6">
                            <div className="mx-auto max-w-7xl">
                                {renderNavigation(true)}
                                <div className="mt-4 rounded-2xl bg-slate-50 p-4">
                                    <p className="truncate text-sm font-semibold text-slate-900">{customerName}</p>
                                    <p className="mt-1 text-sm text-slate-500">{customerPhone}</p>
                                    <div className="mt-2">{renderLogoutButton()}</div>
                                </div>
                            </div>
                        </div>
                    )}
                </header>

                {logoutError && (
                    <div role="alert" className="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                        <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{logoutError}</p>
                    </div>
                )}

                <main id="main-content" className="mx-auto min-h-[calc(100vh-4rem)] max-w-7xl px-4 py-7 sm:px-6 sm:py-9 lg:px-8 lg:py-10">
                    {children}
                </main>
            </div>
        </AppLayout>
    );
}

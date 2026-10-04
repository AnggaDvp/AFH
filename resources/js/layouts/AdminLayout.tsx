import { Link, router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';

type AdminRole = 'admin' | 'staff';
type Item = { label: string; href: string; section: string; staff: boolean; enabled: boolean };

const items: Item[] = [
    { label: 'Dashboard', href: '/admin', section: 'dashboard', staff: true, enabled: true },
    { label: 'Customers', href: '/admin/customers', section: 'customers', staff: true, enabled: true },
    { label: 'Motor Honda', href: '/admin/motors', section: 'motors', staff: false, enabled: true },
    { label: 'Pengajuan Kredit', href: '/admin/pengajuan', section: 'applications', staff: true, enabled: true },
    { label: 'Dana Tunai', href: '/admin/dana-tunai', section: 'dana_tunai', staff: true, enabled: true },
    { label: 'Chat', href: '/admin/chat', section: 'chat', staff: true, enabled: true },
    { label: 'Reports', href: '/admin/reports', section: 'reports', staff: false, enabled: true },
    { label: 'Notifications', href: '/admin/notifications', section: 'notifications', staff: true, enabled: true },
    { label: 'WhatsApp Logs', href: '/admin/whatsapp', section: 'settings', staff: false, enabled: true },
    { label: 'Activity Logs', href: '/admin/activity-logs', section: 'settings', staff: false, enabled: true },
    { label: 'Settings', href: '/admin/settings', section: 'settings', staff: false, enabled: false },
];

type Props = {
    children: ReactNode;
    adminName: string;
    adminRole: AdminRole;
};

export default function AdminLayout({ children, adminName, adminRole }: Props) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [logoutProcessing, setLogoutProcessing] = useState(false);
    const [logoutError, setLogoutError] = useState('');
    const { url, props } = usePage();
    const currentPath = url.split('?')[0].replace(/\/$/, '') || '/';
    const unreadCount = Number((props as Record<string, unknown>).adminUnreadNotifications ?? 0);

    const logout = () => {
        if (logoutProcessing) return;
        setLogoutError('');
        router.post('/admin/logout', {}, {
            onStart: () => setLogoutProcessing(true),
            onError: () => setLogoutError('Sesi belum dapat diakhiri. Silakan coba lagi.'),
            onFinish: () => setLogoutProcessing(false),
        });
    };

    const navigation = (
        <nav aria-label="Navigasi admin" className="space-y-1">
            {items.filter((item) => adminRole === 'admin' || item.staff).map((item) => {
                const active = item.enabled && (item.href === '/admin'
                    ? currentPath === '/admin'
                    : currentPath === item.href || currentPath.startsWith(`${item.href}/`));
                const className = `flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors ${
                    active ? 'bg-red-50 text-red-800' : item.enabled
                        ? 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'
                        : 'cursor-not-allowed text-slate-400'
                }`;
                const icon = <span aria-hidden="true" className="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-bold text-slate-500">
                    {item.label.slice(0, 1)}
                </span>;

                return item.enabled ? (
                    <Link key={item.href} href={item.href} aria-current={active ? 'page' : undefined}
                        onClick={() => setMobileOpen(false)} className={className}>
                        {icon}<span>{item.label}</span>
                    </Link>
                ) : (
                    <span key={item.href} aria-disabled="true" title="Fitur akan tersedia pada tahap berikutnya" className={className}>
                        {icon}<span>{item.label}</span><span className="ml-auto text-[10px] font-medium">Segera</span>
                    </span>
                );
            })}
        </nav>
    );

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-30 flex min-h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
                <div className="flex min-w-0 items-center gap-3">
                    <button type="button" aria-label={mobileOpen ? 'Tutup menu admin' : 'Buka menu admin'}
                        aria-expanded={mobileOpen} aria-controls="admin-sidebar"
                        onClick={() => setMobileOpen((open) => !open)}
                        className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 lg:hidden">
                        <span aria-hidden="true">{mobileOpen ? '×' : '☰'}</span>
                    </button>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold text-slate-950 sm:text-base">Agency Finance Honda</p>
                        <p className="text-xs text-slate-500">Admin Panel</p>
                    </div>
                </div>
                <div className="flex shrink-0 items-center gap-3">
                    <Link href="/admin/notifications" aria-label={`Notifikasi${unreadCount > 0 ? `, ${unreadCount} belum dibaca` : ''}`}
                        className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-50">
                        <span className="sr-only">Notifikasi</span>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" className="h-5 w-5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4" />
                        </svg>
                        {unreadCount > 0 && <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-700 px-1 text-center text-[10px] font-bold leading-5 text-white">{unreadCount > 99 ? '99+' : unreadCount}</span>}
                    </Link>
                    <div className="hidden text-right sm:block">
                        <p className="max-w-48 truncate text-sm font-semibold text-slate-800">{adminName}</p>
                        <p className="text-xs capitalize text-slate-500">{adminRole}</p>
                    </div>
                    <button type="button" onClick={logout} disabled={logoutProcessing}
                        className="min-h-11 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                        <span className="hidden sm:inline">{logoutProcessing ? 'Keluar...' : 'Logout'}</span>
                        <span aria-hidden="true" className="sm:hidden">↪</span>
                    </button>
                </div>
            </header>

            {logoutError && <p role="alert" className="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:px-6">{logoutError}</p>}
            <div className="flex min-h-[calc(100vh-4rem)]">
                <aside id="admin-sidebar" aria-label="Sidebar admin"
                    className={`fixed inset-y-16 left-0 z-20 w-72 overflow-y-auto border-r border-slate-200 bg-white p-4 transition-transform duration-200 lg:sticky lg:top-16 lg:block lg:h-[calc(100vh-4rem)] lg:translate-x-0 ${
                        mobileOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}>
                    <div className="mb-5 rounded-2xl bg-slate-50 p-4 sm:hidden">
                        <p className="truncate text-sm font-semibold">{adminName}</p>
                        <p className="mt-1 text-xs capitalize text-slate-500">{adminRole}</p>
                    </div>
                    {navigation}
                </aside>
                {mobileOpen && <button type="button" aria-label="Tutup menu admin" onClick={() => setMobileOpen(false)}
                    className="fixed inset-x-0 bottom-0 top-16 z-10 bg-slate-950/30 lg:hidden" />}
                <main className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
            </div>
        </div>
    );
}

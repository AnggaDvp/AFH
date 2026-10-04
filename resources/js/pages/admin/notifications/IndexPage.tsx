import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type Notification = {
    id: number;
    type: string;
    title: string;
    message: string;
    url: string | null;
    readAt: string | null;
    createdAt: string;
};

type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    unreadCount: number;
    notifications: {
        data: Notification[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

export default function IndexPage({ adminName, adminRole, notifications, unreadCount }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-5xl">
                <AdminPageHeading title="Notifications" description="Pemberitahuan internal terkait aktivitas customer dan layanan." />
                {unreadCount > 0 && (
                    <button type="button" onClick={() => router.post('/admin/notifications/read-all', {}, { preserveScroll: true })}
                        className="mb-4 min-h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Tandai semua sudah dibaca
                    </button>
                )}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    {notifications.data.length === 0 ? (
                        <p className="p-8 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {notifications.data.map((notification) => (
                                <li key={notification.id} className={`p-4 sm:p-5 ${notification.readAt ? '' : 'bg-red-50/60'}`}>
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold text-slate-900">{notification.title}</h2>
                                                {!notification.readAt && <span className="rounded-full bg-red-700 px-2 py-0.5 text-[10px] font-bold uppercase text-white">Baru</span>}
                                            </div>
                                            <p className="mt-2 whitespace-pre-line text-sm text-slate-600">{notification.message}</p>
                                            <p className="mt-2 text-xs text-slate-500">{formatDate(notification.createdAt)} · {notification.type.replace(/_/g, ' ')}</p>
                                            {notification.url && <Link href={notification.url} className="mt-3 inline-flex min-h-9 items-center text-sm font-semibold text-red-700 hover:underline">Buka detail</Link>}
                                        </div>
                                        {!notification.readAt && (
                                            <button type="button" onClick={() => router.post(`/admin/notifications/${notification.id}/read`, {}, { preserveScroll: true })}
                                                className="min-h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 hover:bg-white">
                                                Tandai dibaca
                                            </button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    <AdminPagination links={notifications.links} from={notifications.from} to={notifications.to} total={notifications.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

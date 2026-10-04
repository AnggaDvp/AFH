import { router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type Notification = {
    id: number;
    eventType: string;
    recipient: string;
    templateName: string | null;
    referenceType: string;
    referenceId: string;
    status: 'queued' | 'sending' | 'sent' | 'failed';
    attempts: number;
    providerMessageId: string | null;
    errorMessage: string | null;
    sentAt: string | null;
    createdAt: string;
};
type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    configuration: {
        enabled: boolean;
        provider: string;
        sender: string;
        recipients: string[];
        template: string;
        deepLinkEnabled: boolean;
    };
    notifications: {
        data: Notification[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { status?: string; event_type?: string; from?: string; to?: string };
};

function formatDate(value: string | null): string {
    return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';
}

export default function IndexPage({ adminName, adminRole, configuration, notifications, filters }: Props) {
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [feedback, setFeedback] = useState('');
    const retry = (notificationId: number) => {
        if (processingId !== null) return;
        setFeedback('');
        router.post(`/admin/whatsapp/${notificationId}/retry`, {}, {
            preserveScroll: true,
            onStart: () => setProcessingId(notificationId),
            onSuccess: () => setFeedback('Permintaan retry diproses. Perbarui daftar untuk melihat status terbaru.'),
            onError: (errors) => setFeedback(errors.whatsapp ?? 'Reminder belum dapat dicoba ulang.'),
            onFinish: () => setProcessingId(null),
        });
    };

    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="WhatsApp Logs" description="Monitoring reminder internal. Log tidak menyimpan isi chat atau file customer." />
                <div className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2 xl:grid-cols-6">
                    <div><span className="block text-xs uppercase text-slate-500">Status</span><strong>{configuration.enabled ? 'Aktif' : 'Nonaktif'}</strong></div>
                    <div><span className="block text-xs uppercase text-slate-500">Provider</span><strong className="capitalize">{configuration.provider}</strong></div>
                    <div><span className="block text-xs uppercase text-slate-500">Sender</span><strong>{configuration.sender}</strong></div>
                    <div><span className="block text-xs uppercase text-slate-500">Recipients</span><strong>{configuration.recipients.join(', ') || '-'}</strong></div>
                    <div><span className="block text-xs uppercase text-slate-500">Template</span><strong>{configuration.template || 'Belum diatur'}</strong></div>
                    <div><span className="block text-xs uppercase text-slate-500">Deep link</span><strong>{configuration.deepLinkEnabled ? 'Aktif' : 'Nonaktif'}</strong></div>
                </div>
                <form action="/admin/whatsapp" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700">
                        Status
                        <select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua status</option>
                            <option value="queued">Queued</option>
                            <option value="sending">Sending</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Event
                        <input name="event_type" maxLength={50} defaultValue={filters.event_type ?? ''} placeholder="Jenis reminder" className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Dari tanggal
                        <input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Sampai tanggal
                        <input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <button type="submit" className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                </form>
                {feedback && <p role="status" className="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">{feedback}</p>}

                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[1120px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th className="px-4 py-3">Event</th><th className="px-4 py-3">Penerima</th>
                                <th className="px-4 py-3">Template</th><th className="px-4 py-3">Referensi</th><th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Percobaan</th><th className="px-4 py-3">Provider ID</th>
                                <th className="px-4 py-3">Dibuat / Terkirim</th><th className="px-4 py-3">Catatan</th><th className="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {notifications.data.map((notification) => (
                                <tr key={notification.id} className="align-top hover:bg-slate-50">
                                    <td className="px-4 py-3">{notification.eventType.replace(/_/g, ' ')}</td>
                                    <td className="px-4 py-3">{notification.recipient}</td>
                                    <td className="px-4 py-3">{notification.templateName ?? '-'}</td>
                                    <td className="px-4 py-3">{notification.referenceType} #{notification.referenceId}</td>
                                    <td className="px-4 py-3"><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize">{notification.status}</span></td>
                                    <td className="px-4 py-3">{notification.attempts}</td>
                                    <td className="max-w-48 truncate px-4 py-3">{notification.providerMessageId ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3">{formatDate(notification.createdAt)}<br /><span className="text-xs text-slate-500">{formatDate(notification.sentAt)}</span></td>
                                    <td className="max-w-72 whitespace-normal px-4 py-3 text-slate-600">{notification.errorMessage ?? '-'}</td>
                                    <td className="px-4 py-3">{notification.status === 'failed' && <button type="button" disabled={processingId !== null} onClick={() => retry(notification.id)} className="min-h-9 rounded-lg bg-red-700 px-3 text-xs font-semibold text-white hover:bg-red-800 disabled:opacity-50">{processingId === notification.id ? 'Memproses...' : 'Coba ulang'}</button>}</td>
                                </tr>
                            ))}
                            {notifications.data.length === 0 && <tr><td colSpan={10} className="px-4 py-10 text-center text-slate-500">Belum ada reminder WhatsApp.</td></tr>}
                        </tbody>
                    </table>
                    <AdminPagination links={notifications.links} from={notifications.from} to={notifications.to} total={notifications.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

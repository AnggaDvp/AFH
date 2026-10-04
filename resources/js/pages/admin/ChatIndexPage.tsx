import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPagination from '@/components/AdminPagination';
import useChatRefresh from '@/useChatRefresh';

type ConversationSummary = {
    id: number;
    customerName: string;
    customerPhone: string;
    serviceLabel: string;
    motorName: string | null;
    lastMessage: string | null;
    status: string;
    unreadCount: number;
    lastMessageAt: string | null;
};

type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    conversations: {
        data: ConversationSummary[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; status?: string; context?: string; unread?: string };
};

function formatTime(value: string | null): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function Service({ conversation }: { conversation: ConversationSummary }) {
    return (
        <span>
            <span className="block font-semibold text-slate-900">{conversation.serviceLabel}</span>
            {conversation.motorName && <span className="mt-1 block text-xs text-slate-500">Motor: {conversation.motorName}</span>}
        </span>
    );
}

export default function ChatIndexPage({ adminName, adminRole, conversations, filters }: Props) {
    useChatRefresh('conversations');
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <h1 className="text-2xl font-bold text-slate-950 sm:text-3xl">Percakapan Customer</h1>
                <p className="mt-2 text-sm text-slate-600">Balas pesan customer melalui website.</p>

                <form action="/admin/chat" method="get" className="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700 xl:col-span-2">Customer atau telepon
                        <input name="search" defaultValue={filters.search ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">Layanan
                        <select name="context" defaultValue={filters.context ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua layanan</option><option value="motor">Motor Honda</option><option value="dana_tunai">Dana Tunai</option>
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">Status
                        <select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua status</option><option value="open">Open</option><option value="closed">Closed</option>
                        </select>
                    </label>
                    <div className="flex items-end gap-3">
                        <label className="flex min-h-11 items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="unread" value="1" defaultChecked={filters.unread === '1'} className="h-4 w-4 rounded border-slate-300 text-red-700 focus:ring-red-600" />
                            Belum dibaca
                        </label>
                        <button type="submit" className="min-h-11 flex-1 rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                    </div>
                </form>

                {conversations.data.length === 0 ? (
                    <div className="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600">
                        Belum ada percakapan.
                    </div>
                ) : (
                    <>
                        <div className="mt-6 grid gap-3 md:hidden">
                            {conversations.data.map((conversation) => (
                                <Link
                                    key={conversation.id}
                                    href={`/admin/chat/${conversation.id}`}
                                    className="rounded-2xl border border-slate-200 bg-white p-4 hover:border-red-200"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-slate-950">{conversation.customerName}</p>
                                            <p className="mt-1 text-xs text-slate-500">{conversation.customerPhone}</p>
                                        </div>
                                        {conversation.unreadCount > 0 && (
                                            <span className="rounded-full bg-red-700 px-2.5 py-1 text-xs font-semibold text-white">
                                                {conversation.unreadCount} baru
                                            </span>
                                        )}
                                    </div>
                                    <div className="mt-3"><Service conversation={conversation} /></div>
                                    <p className="mt-3 line-clamp-2 text-sm text-slate-600">{conversation.lastMessage || 'Belum ada pesan'}</p>
                                    <div className="mt-3 flex justify-between text-xs text-slate-500">
                                        <span>{conversation.status === 'open' ? 'Aktif' : 'Ditutup'}</span>
                                        <time>{formatTime(conversation.lastMessageAt)}</time>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        <div className="mt-6 hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white md:block">
                            <table className="w-full min-w-[900px] border-collapse text-left text-sm">
                                <thead className="bg-slate-100 text-xs uppercase tracking-wide text-slate-600">
                                    <tr>
                                        <th className="px-4 py-3">Customer</th>
                                        <th className="px-4 py-3">Service</th>
                                        <th className="px-4 py-3">Last Message</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Unread</th>
                                        <th className="px-4 py-3">Time</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {conversations.data.map((conversation) => (
                                        <tr key={conversation.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-4">
                                                <Link href={`/admin/chat/${conversation.id}`} className="font-semibold text-slate-900 hover:text-red-700">
                                                    {conversation.customerName}
                                                </Link>
                                                <p className="mt-1 text-xs text-slate-500">{conversation.customerPhone}</p>
                                            </td>
                                            <td className="px-4 py-4"><Service conversation={conversation} /></td>
                                            <td className="max-w-sm truncate px-4 py-4 text-slate-600">{conversation.lastMessage || '-'}</td>
                                            <td className="px-4 py-4 text-slate-700">{conversation.status === 'open' ? 'Aktif' : 'Ditutup'}</td>
                                            <td className="px-4 py-4 text-slate-700">{conversation.unreadCount}</td>
                                            <td className="px-4 py-4 text-slate-500">{formatTime(conversation.lastMessageAt)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
                <div className="mt-4 rounded-2xl border border-slate-200 bg-white">
                    <AdminPagination links={conversations.links} from={conversations.from} to={conversations.to} total={conversations.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

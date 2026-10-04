import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type LogEntry = {
    id: number;
    admin: string;
    action: string;
    entityType: string | null;
    entityId: string | null;
    metadata: Record<string, unknown> | null;
    createdAt: string;
};

type Props = {
    adminName: string;
    adminRole: 'admin';
    logs: {
        data: LogEntry[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { action?: string; entity_type?: string; from?: string; to?: string };
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

export default function IndexPage({ adminName, adminRole, logs, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Activity Logs" description="Riwayat tindakan penting yang dilakukan melalui Admin Panel." />
                <form action="/admin/activity-logs" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700">Action
                        <input name="action" defaultValue={filters.action ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">Entity type
                        <input name="entity_type" defaultValue={filters.entity_type ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">Dari
                        <input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">Sampai
                        <input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <button type="submit" className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[850px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr><th className="px-4 py-3">Tanggal</th><th className="px-4 py-3">Admin</th><th className="px-4 py-3">Action</th><th className="px-4 py-3">Entity</th><th className="px-4 py-3">Metadata</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {logs.data.map((log) => (
                                <tr key={log.id} className="align-top">
                                    <td className="whitespace-nowrap px-4 py-3 text-slate-600">{formatDate(log.createdAt)}</td>
                                    <td className="px-4 py-3 font-medium">{log.admin}</td>
                                    <td className="px-4 py-3 font-mono text-xs">{log.action}</td>
                                    <td className="px-4 py-3 text-slate-600">{log.entityType ? `${log.entityType} #${log.entityId ?? '-'}` : '-'}</td>
                                    <td className="max-w-md whitespace-pre-wrap break-words px-4 py-3 text-xs text-slate-600">{log.metadata ? JSON.stringify(log.metadata) : '-'}</td>
                                </tr>
                            ))}
                            {logs.data.length === 0 && <tr><td colSpan={5} className="px-4 py-10 text-center text-slate-500">Belum ada aktivitas yang tercatat.</td></tr>}
                        </tbody>
                    </table>
                    <AdminPagination links={logs.links} from={logs.from} to={logs.to} total={logs.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

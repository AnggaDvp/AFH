import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type Application = {
    id: number; number: string; customer: string; phone: string; status: string;
    source: string | null; conversationId: number | null; lastActivity: string | null; createdAt: string;
};
type Props = {
    adminName: string; adminRole: 'admin' | 'staff';
    applications: { data: Application[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    statuses: string[]; filters: { search?: string; status?: string; from?: string; to?: string };
};
const labels: Record<string, string> = { draft: 'Draft', submitted: 'Dikirim', under_review: 'Review', need_documents: 'Perlu dokumen', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' };
const date = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

export default function IndexPage({ adminName, adminRole, applications, statuses, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Dana Tunai / Gadai BPKB" description="Daftar application Dana Tunai yang sudah tersimpan. Minat layanan melalui chat tetap terlihat pada halaman Chat." />
                <form action="/admin/dana-tunai" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700 xl:col-span-2">Nomor, nama, atau telepon<input name="search" defaultValue={filters.search ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Status<select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3"><option value="">Semua</option>{statuses.map((status) => <option key={status} value={status}>{labels[status] ?? status}</option>)}</select></label>
                    <label className="text-sm font-medium text-slate-700">Dari<input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Sampai<input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <button type="submit" className="min-h-11 rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[950px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600"><tr><th className="px-4 py-3">Nomor</th><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Layanan</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Conversation</th><th className="px-4 py-3">Source</th><th className="px-4 py-3">Aktivitas terakhir</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{applications.data.map((application) => <tr key={application.id}>
                            <td className="px-4 py-3"><Link href={`/admin/dana-tunai/${application.id}`} className="font-semibold text-red-700 hover:underline">{application.number}</Link></td>
                            <td className="px-4 py-3">{application.customer}<p className="mt-1 text-xs text-slate-500">{application.phone}</p></td>
                            <td className="px-4 py-3">Dana Tunai / Gadai BPKB</td><td className="px-4 py-3">{labels[application.status] ?? application.status}</td>
                            <td className="px-4 py-3">{application.conversationId ? <Link href={`/admin/chat/${application.conversationId}`} className="font-semibold text-red-700 hover:underline">Buka chat</Link> : '-'}</td>
                            <td className="px-4 py-3">{application.source ?? '-'}</td><td className="px-4 py-3">{date(application.lastActivity)}</td>
                        </tr>)}{applications.data.length === 0 && <tr><td colSpan={7} className="px-4 py-10 text-center text-slate-500">Belum ada application Dana Tunai.</td></tr>}</tbody>
                    </table>
                    <AdminPagination links={applications.links} from={applications.from} to={applications.to} total={applications.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

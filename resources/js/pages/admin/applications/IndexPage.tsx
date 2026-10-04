import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type Application = {
    id: number; number: string; customer: string; phone: string; motor: string | null;
    cashPrice: number | string | null; downPayment: number | string | null; tenorMonths: number | null;
    installmentSource: string | null; status: string; source: string | null;
    createdAt: string; submittedAt: string | null; updatedAt: string;
};
type Props = {
    adminName: string; adminRole: 'admin' | 'staff';
    applications: { data: Application[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    motors: { id: number; name: string }[]; statuses: string[];
    filters: { search?: string; status?: string; motor_id?: string; source?: string; from?: string; to?: string };
};
const statusLabels: Record<string, string> = { draft: 'Draft', submitted: 'Dikirim', under_review: 'Review', need_documents: 'Perlu dokumen', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' };
const money = (value: number | string | null) => value === null ? 'Belum tersedia' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

export default function IndexPage({ adminName, adminRole, applications, motors, statuses, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Pengajuan Kredit" description="Daftar dan filter pengajuan yang tersimpan di database." />
                <form action="/admin/pengajuan" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-6">
                    <label className="text-sm font-medium text-slate-700 xl:col-span-2">Nomor, nama, atau telepon<input name="search" defaultValue={filters.search ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Status<select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3"><option value="">Semua</option>{statuses.map((status) => <option key={status} value={status}>{statusLabels[status] ?? status}</option>)}</select></label>
                    <label className="text-sm font-medium text-slate-700">Motor<select name="motor_id" defaultValue={filters.motor_id ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3"><option value="">Semua motor</option>{motors.map((motor) => <option key={motor.id} value={motor.id}>{motor.name}</option>)}</select></label>
                    <label className="text-sm font-medium text-slate-700">Dari<input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Sampai<input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Source<input name="source" defaultValue={filters.source ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <button type="submit" className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[1200px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600"><tr>
                            <th className="px-4 py-3">Nomor</th><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Motor</th>
                            <th className="px-4 py-3">Harga cash</th><th className="px-4 py-3">DP</th><th className="px-4 py-3">Tenor</th>
                            <th className="px-4 py-3">Cicilan source</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Source</th><th className="px-4 py-3">Tanggal</th>
                        </tr></thead>
                        <tbody className="divide-y divide-slate-100">{applications.data.map((application) => <tr key={application.id}>
                            <td className="px-4 py-3"><Link href={`/admin/pengajuan/${application.id}`} className="font-semibold text-red-700 hover:underline">{application.number}</Link></td>
                            <td className="px-4 py-3"><p>{application.customer}</p><p className="mt-1 text-xs text-slate-500">{application.phone}</p></td>
                            <td className="px-4 py-3">{application.motor ?? '-'}</td><td className="px-4 py-3">{money(application.cashPrice)}</td>
                            <td className="px-4 py-3">{money(application.downPayment)}</td><td className="px-4 py-3">{application.tenorMonths ? `${application.tenorMonths} bulan` : '-'}</td>
                            <td className="px-4 py-3">{application.installmentSource ?? '-'}</td><td className="px-4 py-3">{statusLabels[application.status] ?? application.status}</td>
                            <td className="px-4 py-3">{application.source ?? '-'}</td><td className="px-4 py-3">{new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(application.createdAt))}</td>
                        </tr>)}{applications.data.length === 0 && <tr><td colSpan={10} className="px-4 py-10 text-center text-slate-500">Belum ada pengajuan pada filter ini.</td></tr>}</tbody>
                    </table>
                    <AdminPagination links={applications.links} from={applications.from} to={applications.to} total={applications.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

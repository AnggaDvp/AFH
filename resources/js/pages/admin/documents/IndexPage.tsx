import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';
import { formatFileSize } from '@/types/chat';

type DocumentRow = { id: number; customerId: number; customer: string; phone: string; type: string; filename: string; mime: string; size: number; status: string; uploadedAt: string | null; downloadUrl: string };
type Props = {
    adminName: string; adminRole: 'admin' | 'staff';
    documents: { data: DocumentRow[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    filters: { search?: string; status?: string; type?: string };
};

export default function IndexPage({ adminName, adminRole, documents, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Dokumen" description="Metadata dokumen aplikasi. Isi file hanya tersedia melalui route private yang terotorisasi." />
                <form action="/admin/documents" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label className="text-sm font-medium text-slate-700 xl:col-span-2">Nama file, customer, atau telepon<input name="search" defaultValue={filters.search ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Status<select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3"><option value="">Semua</option><option value="uploaded">Menunggu pemeriksaan</option><option value="reviewed">Ditinjau</option><option value="need_replacement">Perlu penggantian</option></select></label>
                    <label className="text-sm font-medium text-slate-700">Tipe<input name="type" defaultValue={filters.type ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <button type="submit" className="min-h-11 rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Filter</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[950px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600"><tr><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Tipe</th><th className="px-4 py-3">Filename</th><th className="px-4 py-3">MIME</th><th className="px-4 py-3">Ukuran</th><th className="px-4 py-3">Diunggah</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">File</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{documents.data.map((document) => <tr key={document.id}>
                            <td className="px-4 py-3"><Link href={`/admin/customers/${document.customerId}`} className="font-semibold text-red-700 hover:underline">{document.customer}</Link><p className="mt-1 text-xs text-slate-500">{document.phone}</p></td>
                            <td className="px-4 py-3">{document.type}</td><td className="px-4 py-3 break-all">{document.filename}</td>
                            <td className="px-4 py-3">{document.mime}</td><td className="px-4 py-3">{formatFileSize(document.size)}</td>
                            <td className="px-4 py-3">{document.uploadedAt ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(document.uploadedAt)) : '-'}</td>
                            <td className="px-4 py-3">{document.status}</td><td className="px-4 py-3"><a href={document.downloadUrl} className="font-semibold text-red-700 hover:underline">Download</a></td>
                        </tr>)}{documents.data.length === 0 && <tr><td colSpan={8} className="px-4 py-10 text-center text-slate-500">Belum ada dokumen aplikasi.</td></tr>}</tbody>
                    </table>
                    <AdminPagination links={documents.links} from={documents.from} to={documents.to} total={documents.total} />
                </div>
            </section>
        </AdminLayout>
    );
}

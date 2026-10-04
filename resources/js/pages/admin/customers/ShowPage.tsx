import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';
import { formatFileSize } from '@/types/chat';

type Application = { id: number; number: string; motor: string | null; status: string; createdAt: string };
type DanaApplication = { id: number; number: string; status: string; createdAt: string; conversationId: number | null };
type Conversation = { id: number; contextType: 'motor' | 'dana_tunai'; service: string; motor: string | null; status: string; lastMessage: string; lastActivity: string | null };
type Document = { id: number; type: string; name: string; mimeType: string; size: number; status: string; uploadedAt: string | null; downloadUrl: string };
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    customer: { id: number; name: string; phone: string; region: string | null; district: string | null; village: string | null; postalCode: string; streetAddress: string; createdAt: string };
    creditApplications: Paginated<Application>;
    danaTunaiApplications: Paginated<DanaApplication>;
    conversations: Paginated<Conversation>;
    documents: Paginated<Document>;
    recentActivity: { at: string | null; label: string; url: string }[];
};

function Section({ title, children }: { title: string; children: ReactNode }) {
    return <section className="rounded-2xl border border-slate-200 bg-white p-5"><h2 className="text-base font-semibold text-slate-900">{title}</h2><div className="mt-4">{children}</div></section>;
}

export default function ShowPage({ adminName, adminRole, customer, creditApplications, danaTunaiApplications, conversations, documents, recentActivity }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-5xl space-y-5">
                <Link href="/admin/customers" className="inline-flex min-h-10 items-center text-sm font-semibold text-red-700 hover:underline">Kembali ke customers</Link>
                <AdminPageHeading title={customer.name} description={`Customer #${customer.id} · Terdaftar ${new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(customer.createdAt))}`} />
                <Section title="Profil dan alamat">
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt className="text-slate-500">No. telepon</dt><dd className="mt-1 font-medium">{customer.phone}</dd></div>
                        <div><dt className="text-slate-500">Wilayah</dt><dd className="mt-1 font-medium">{customer.region ?? '-'}</dd></div>
                        <div><dt className="text-slate-500">Kecamatan</dt><dd className="mt-1 font-medium">{customer.district ?? '-'}</dd></div>
                        <div><dt className="text-slate-500">Kelurahan/Desa</dt><dd className="mt-1 font-medium">{customer.village ?? '-'}</dd></div>
                        <div><dt className="text-slate-500">Kode pos</dt><dd className="mt-1 font-medium">{customer.postalCode}</dd></div>
                        <div className="sm:col-span-2"><dt className="text-slate-500">Alamat jalan</dt><dd className="mt-1 font-medium">{customer.streetAddress}</dd></div>
                    </dl>
                </Section>
                <Section title="Pengajuan kredit">
                    {creditApplications.total === 0 ? <p className="text-sm text-slate-500">Belum ada pengajuan kredit.</p> : (
                        <ul className="divide-y divide-slate-100">{creditApplications.data.map((application) => (
                            <li key={application.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                                <span><Link href={`/admin/pengajuan/${application.id}`} className="font-semibold text-red-700 hover:underline">{application.number}</Link><span className="ml-2 text-slate-600">{application.motor ?? 'Motor belum dipilih'}</span></span>
                                <span className="text-slate-600">{application.status}</span>
                            </li>
                        ))}</ul>
                    )}
                    <AdminPagination links={creditApplications.links} from={creditApplications.from} to={creditApplications.to} total={creditApplications.total} />
                </Section>
                <Section title="Dana Tunai">
                    {danaTunaiApplications.total === 0 ? <p className="text-sm text-slate-500">Belum ada pengajuan Dana Tunai. Minat layanan tetap tersedia di daftar chat.</p> : (
                        <ul className="divide-y divide-slate-100">{danaTunaiApplications.data.map((application) => (
                            <li key={application.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                                <Link href={`/admin/dana-tunai/${application.id}`} className="font-semibold text-red-700 hover:underline">{application.number}</Link>
                                <span className="text-slate-600">{application.status}</span>
                            </li>
                        ))}</ul>
                    )}
                    <AdminPagination links={danaTunaiApplications.links} from={danaTunaiApplications.from} to={danaTunaiApplications.to} total={danaTunaiApplications.total} />
                </Section>
                <Section title="Percakapan">
                    {conversations.data.length === 0 ? <p className="text-sm text-slate-500">Belum ada percakapan.</p> : (
                        <ul className="divide-y divide-slate-100">{conversations.data.map((conversation) => (
                            <li key={conversation.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                                <span><Link href={`/admin/chat/${conversation.id}`} className="font-semibold text-red-700 hover:underline">{conversation.service}</Link>{conversation.motor && <span className="ml-2 text-slate-600">{conversation.motor}</span>}<span className="ml-2 text-slate-500">{conversation.lastMessage}</span></span>
                                <span className="text-slate-500">{conversation.status}</span>
                            </li>
                        ))}</ul>
                    )}
                    <AdminPagination links={conversations.links} from={conversations.from} to={conversations.to} total={conversations.total} />
                </Section>
                <Section title="Dokumen terkait">
                    {documents.total === 0 ? <p className="text-sm text-slate-500">Belum ada dokumen aplikasi yang tercatat.</p> : (
                        <ul className="divide-y divide-slate-100">{documents.data.map((document) => (
                            <li key={document.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                                <span><span className="font-medium">{document.type}</span><span className="ml-2 text-slate-600">{document.name}</span><span className="ml-2 text-xs text-slate-500">{formatFileSize(document.size)}</span></span>
                                <a href={document.downloadUrl} className="min-h-10 text-sm font-semibold text-red-700 hover:underline">Download</a>
                            </li>
                        ))}</ul>
                    )}
                    <AdminPagination links={documents.links} from={documents.from} to={documents.to} total={documents.total} />
                </Section>
                <Section title="Aktivitas terbaru">
                    {recentActivity.length === 0 ? <p className="text-sm text-slate-500">Belum ada aktivitas tercatat.</p> : (
                        <ul className="divide-y divide-slate-100">{recentActivity.map((activity, index) => (
                            <li key={`${activity.url}-${index}`} className="py-3 text-sm">
                                <Link href={activity.url} className="font-medium text-red-700 hover:underline">{activity.label}</Link>
                                {activity.at && <p className="mt-1 text-xs text-slate-500">{new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(activity.at))}</p>}
                            </li>
                        ))}</ul>
                    )}
                </Section>
            </section>
        </AdminLayout>
    );
}

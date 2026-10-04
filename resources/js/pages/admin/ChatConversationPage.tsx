import ChatComposer from '@/components/ChatComposer';
import ChatAttachments from '@/components/ChatAttachments';
import useChatRefresh from '@/useChatRefresh';
import AdminLayout from '@/layouts/AdminLayout';
import type { ChatMessage } from '@/types/chat';

type Conversation = {
    id: number;
    customerName: string;
    customerPhone: string;
    contextType: 'motor' | 'dana_tunai';
    serviceLabel: string;
    motorName: string | null;
    cashLoanRequest: {
        nopol: string;
        motorBrand: string;
        stnkName: string;
        houseOwnership: string;
        status: string;
    } | null;
    status: string;
    messages: ChatMessage[];
};

type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    conversation: Conversation;
};

function formatTime(value: string | null): string {
    if (!value) return '';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function ChatConversationPage({ adminName, adminRole, conversation }: Props) {
    useChatRefresh('conversation');

    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto flex min-h-[calc(100vh-9rem)] w-full max-w-5xl flex-col">
                <div className="rounded-t-2xl border border-slate-200 bg-white px-5 py-4">
                    <h1 className="text-lg font-bold text-slate-950">{conversation.customerName}</h1>
                    <p className="mt-1 text-sm text-slate-500">{conversation.customerPhone}</p>
                    <p className="mt-3 text-sm font-semibold text-red-700">{conversation.serviceLabel}</p>
                    {conversation.contextType === 'motor' && conversation.motorName && (
                        <p className="mt-1 text-sm text-slate-700">Motor: {conversation.motorName}</p>
                    )}
                    {conversation.contextType === 'dana_tunai' && (
                        <p className="mt-1 text-sm text-slate-700">Layanan: Dana Tunai / Gadai BPKB</p>
                    )}
                </div>

                {conversation.cashLoanRequest && (
                    <section className="border-x border-slate-200 bg-white px-5 py-4">
                        <h2 className="text-sm font-semibold text-slate-900">Pengajuan awal Dana Tunai</h2>
                        <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt className="text-slate-500">Nopol</dt><dd className="mt-0.5 font-medium">{conversation.cashLoanRequest.nopol}</dd></div>
                            <div><dt className="text-slate-500">Tipe Motor</dt><dd className="mt-0.5 font-medium">{conversation.cashLoanRequest.motorBrand}</dd></div>
                            <div><dt className="text-slate-500">Nama STNK</dt><dd className="mt-0.5 font-medium">{conversation.cashLoanRequest.stnkName}</dd></div>
                            <div><dt className="text-slate-500">Status Rumah</dt><dd className="mt-0.5 font-medium">{conversation.cashLoanRequest.houseOwnership}</dd></div>
                        </dl>
                    </section>
                )}

                <div aria-live="polite" className="flex-1 space-y-4 overflow-y-auto border-x border-slate-200 bg-slate-100 p-4 sm:p-6">
                    {conversation.messages.map((message) => {
                        const isAdmin = message.senderType === 'admin';

                        return (
                            <div key={message.id} className={`flex ${isAdmin ? 'justify-end' : 'justify-start'}`}>
                                <article className={`max-w-[88%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[75%] ${
                                    isAdmin ? 'rounded-br-md bg-slate-800 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-900'
                                }`}>
                                    {message.senderType === 'system' && (
                                        <p className="mb-1.5 text-xs font-semibold text-red-700">{message.senderName || 'Affiyah'}</p>
                                    )}
                                    {message.body && <p className="whitespace-pre-wrap break-words text-sm leading-6">{message.body}</p>}
                                    <ChatAttachments attachments={message.attachments} />
                                    <div className={`mt-2 flex items-center justify-end gap-2 text-[11px] ${
                                        isAdmin ? 'text-slate-300' : 'text-slate-500'
                                    }`}>
                                        <time>{formatTime(message.createdAt)}</time>
                                        {isAdmin && <span>{message.readAt ? 'Dibaca' : 'Terkirim'}</span>}
                                    </div>
                                </article>
                            </div>
                        );
                    })}
                </div>

                <div className="rounded-b-2xl border border-slate-200 bg-white p-4">
                    {conversation.status === 'open' ? (
                        <ChatComposer key={conversation.id} url={`/admin/chat/${conversation.id}/messages`} label="Balas" />
                    ) : (
                        <p className="text-sm text-slate-600">Percakapan ini sudah ditutup.</p>
                    )}
                </div>
            </section>
        </AdminLayout>
    );
}

import { Link } from '@inertiajs/react';
import ChatComposer from '@/components/ChatComposer';
import ChatAttachments from '@/components/ChatAttachments';
import CustomerLayout from '@/layouts/CustomerLayout';
import useChatRefresh from '@/useChatRefresh';
import type { CustomerPageProps } from '@/types/customer';
import type { ChatMessage } from '@/types/chat';

type Conversation = {
    id: number;
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

type Props = CustomerPageProps & {
    conversation: Conversation;
};

function formatTime(value: string | null): string {
    if (!value) return '';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function ConversationPage(props: Props) {
    useChatRefresh('conversation');

    return (
        <CustomerLayout {...props}>
            <section className="mx-auto flex h-[calc(100dvh-7rem)] min-h-[30rem] max-w-4xl flex-col">
                <Link href="/customer/chat" className="inline-flex min-h-11 items-center self-start text-sm font-semibold text-brand hover:underline focus-visible:outline-none focus-visible:underline">
                    &larr; Kembali ke daftar chat
                </Link>

                <div className="mt-3 rounded-t-2xl border border-slate-200 bg-white px-4 py-4 sm:px-5">
                    <p className="text-sm font-semibold text-red-700">{props.conversation.serviceLabel}</p>
                    {props.conversation.contextType === 'motor' && props.conversation.motorName && (
                        <h1 className="mt-1 text-lg font-bold text-slate-950">Motor: {props.conversation.motorName}</h1>
                    )}
                    {props.conversation.contextType === 'dana_tunai' && <h1 className="sr-only">Dana Tunai / Gadai BPKB</h1>}
                    <p className="mt-1 text-xs text-slate-500">
                        Status: {props.conversation.status === 'open' ? 'Aktif' : 'Selesai'}
                    </p>
                </div>

                {props.conversation.cashLoanRequest && (
                    <details className="group relative z-20 shrink-0 border-x border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
                        <summary className="flex min-h-8 cursor-pointer items-center justify-between text-sm font-semibold text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            Ringkasan pengajuan awal
                            <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 text-slate-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="m4 7 6 6 6-6" />
                            </svg>
                        </summary>
                        <dl className="absolute inset-x-0 top-full grid max-h-52 gap-3 overflow-y-auto rounded-b-xl border border-slate-200 bg-white p-4 text-sm shadow-soft sm:grid-cols-2">
                            <div><dt className="text-slate-500">Nopol</dt><dd className="mt-0.5 font-medium">{props.conversation.cashLoanRequest.nopol}</dd></div>
                            <div><dt className="text-slate-500">Tipe Motor</dt><dd className="mt-0.5 font-medium">{props.conversation.cashLoanRequest.motorBrand}</dd></div>
                            <div><dt className="text-slate-500">Nama STNK</dt><dd className="mt-0.5 font-medium">{props.conversation.cashLoanRequest.stnkName}</dd></div>
                            <div><dt className="text-slate-500">Status Rumah</dt><dd className="mt-0.5 font-medium">{props.conversation.cashLoanRequest.houseOwnership}</dd></div>
                        </dl>
                    </details>
                )}

                <div aria-live="polite" className="min-h-0 min-w-0 flex-1 space-y-4 overflow-y-auto border-x border-slate-200 bg-slate-50 p-3 sm:p-6">
                    {props.conversation.messages.map((message) => {
                        const isCustomer = message.senderType === 'customer';

                        return (
                            <div key={message.id} className={`flex ${isCustomer ? 'justify-end' : 'justify-start'}`}>
                                <article className={`min-w-0 max-w-[88%] rounded-2xl px-4 py-3 sm:max-w-[75%] ${
                                    isCustomer ? 'rounded-br-md bg-brand text-white' : message.senderType === 'system'
                                        ? 'rounded-bl-md border border-red-100 bg-red-50 text-slate-900'
                                        : 'rounded-bl-md border border-slate-200 bg-white text-slate-900'
                                }`}>
                                    {message.senderType === 'system' && (
                                        <p className="mb-1.5 text-xs font-semibold text-red-700">{message.senderName || 'Affiyah'}</p>
                                    )}
                                    {message.body && <p className="whitespace-pre-wrap break-words text-sm leading-6">{message.body}</p>}
                                    <ChatAttachments attachments={message.attachments} />
                                    <div className={`mt-2 flex items-center justify-end gap-2 text-[11px] ${
                                        isCustomer ? 'text-red-100' : 'text-slate-500'
                                    }`}>
                                        <time>{formatTime(message.createdAt)}</time>
                                        {isCustomer && <span>{message.readAt ? 'Dibaca' : 'Terkirim'}</span>}
                                    </div>
                                </article>
                            </div>
                        );
                    })}
                </div>

                <div className="shrink-0 rounded-b-2xl border border-slate-200 bg-white p-3 sm:p-4">
                    {props.conversation.status === 'open' ? (
                        <ChatComposer key={props.conversation.id} url={`/customer/chat/${props.conversation.id}/messages`} />
                    ) : (
                        <p className="text-sm text-slate-600">Percakapan ini sudah ditutup.</p>
                    )}
                </div>
            </section>
        </CustomerLayout>
    );
}

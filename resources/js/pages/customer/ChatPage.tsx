import { Link } from '@inertiajs/react';
import CustomerEmptyState from '@/components/CustomerEmptyState';
import CustomerPageHeader from '@/components/CustomerPageHeader';
import CustomerLayout from '@/layouts/CustomerLayout';
import useChatRefresh from '@/useChatRefresh';
import type { CustomerPageProps } from '@/types/customer';

type ConversationSummary = {
    id: number;
    contextType: 'motor' | 'dana_tunai';
    serviceLabel: string;
    motorName: string | null;
    lastMessage: string | null;
    unreadCount: number;
    lastMessageAt: string | null;
    status: string;
};

type Props = CustomerPageProps & {
    conversations: ConversationSummary[];
};

function formatTime(value: string | null): string {
    if (!value) return '';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function ChatPage(props: Props) {
    useChatRefresh('conversations');
    return (
        <CustomerLayout {...props}>
            <section className="mx-auto max-w-4xl">
                <CustomerPageHeader eyebrow="Bantuan" title="Chat dengan Tim Kami" description="Percakapan layanan Anda tersedia di sini. Pilih percakapan untuk melanjutkan konsultasi." />

                {props.conversations.length > 0 ? (
                    <div className="mt-7 grid gap-3">
                        {props.conversations.map((conversation) => (
                            <Link
                                key={conversation.id}
                                href={`/customer/chat/${conversation.id}`}
                                className="customer-card block min-w-0 p-5 transition-colors duration-300 hover:border-red-200 hover:bg-red-50/40 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-100"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-red-700">{conversation.serviceLabel}</p>
                                        {conversation.motorName && (
                                            <h2 className="mt-1 text-lg font-bold text-slate-950">{conversation.motorName}</h2>
                                        )}
                                        <p className="mt-2 line-clamp-2 break-words text-sm text-slate-600">
                                            {conversation.lastMessage || 'Percakapan baru'}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-3 sm:flex-col sm:items-end">
                                        {conversation.lastMessageAt && (
                                            <time className="text-xs text-slate-500">{formatTime(conversation.lastMessageAt)}</time>
                                        )}
                                        {conversation.unreadCount > 0 && (
                                            <span className="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">
                                                {conversation.unreadCount} pesan baru
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="mt-7">
                        <CustomerEmptyState
                            title="Belum ada percakapan"
                            description="Mulai dari halaman layanan untuk menghubungi tim Agency Finance Honda."
                            action={<Link href="/customer/motor" className="customer-button-secondary">Lihat layanan motor</Link>}
                        />
                    </div>
                )}
            </section>
        </CustomerLayout>
    );
}

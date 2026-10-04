import type { ReactNode } from 'react';

type Props = {
    title: string;
    description: string;
    action?: ReactNode;
    icon?: ReactNode;
};

export default function CustomerEmptyState({ title, description, action, icon }: Props) {
    return (
        <div className="customer-card flex flex-col items-center border-dashed px-5 py-10 text-center sm:py-12">
            {icon && <span aria-hidden="true" className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-subtle text-brand">{icon}</span>}
            <h2 className="mt-4 text-lg font-bold text-slate-950">{title}</h2>
            <p className="mt-2 max-w-md text-sm leading-6 text-slate-600">{description}</p>
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}

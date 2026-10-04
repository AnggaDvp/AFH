import { useCallback, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import CustomerGateModal from '@/components/CustomerGateModal';
import FloatingChatbot from '@/components/FloatingChatbot';
import PublicLandingView from '@/components/PublicLandingView';

export default function Home() {
    const [isGateOpen, setIsGateOpen] = useState(false);
    const openGate = useCallback(() => setIsGateOpen(true), []);
    const closeGate = useCallback(() => setIsGateOpen(false), []);

    return (
        <AppLayout>
            <PublicLandingView onRequestAccess={openGate} />
            <FloatingChatbot onRequestGate={openGate} />
            <CustomerGateModal open={isGateOpen} onClose={closeGate} />
        </AppLayout>
    );
}

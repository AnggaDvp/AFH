import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function useChatRefresh(prop: 'conversation' | 'conversations') {
    useEffect(() => {
        let visiting = false;
        const stopStart = router.on('start', () => { visiting = true; });
        const stopFinish = router.on('finish', () => { visiting = false; });
        const timer = window.setInterval(() => {
            if (document.visibilityState !== 'visible' || visiting) return;

            router.reload({
                only: [prop],
            });
        }, 5000);

        return () => {
            window.clearInterval(timer);
            stopStart();
            stopFinish();
        };
    }, [prop]);
}

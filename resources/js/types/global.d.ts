import type { Auth } from '@/types/auth';
import type { WeddingShared } from '@/types/wedding';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            wedding: WeddingShared;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

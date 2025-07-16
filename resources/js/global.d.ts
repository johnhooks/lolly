declare global {
    interface Window {
        lolly?: {
            schema: any;
            preloadedData: Record<string, any>;
            nonce: string;
        };
    }
}

export {};

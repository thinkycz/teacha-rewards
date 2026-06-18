declare module 'qrcode' {
    export interface QRCodeToDataURLOptions {
        errorCorrectionLevel?: 'L' | 'M' | 'Q' | 'H';
        type?: 'image/png' | 'image/jpeg' | 'image/webp';
        margin?: number;
        width?: number;
        color?: {
            dark?: string;
            light?: string;
        };
    }
    export function toDataURL(
        text: string,
        options?: QRCodeToDataURLOptions,
    ): Promise<string>;
    export function toString(
        text: string,
        options?: QRCodeToDataURLOptions,
    ): Promise<string>;
    export function toCanvas(
        canvas: HTMLCanvasElement,
        text: string,
        options?: QRCodeToDataURLOptions,
    ): Promise<void>;
}

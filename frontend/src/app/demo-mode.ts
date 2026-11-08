export const DEMO_MODE = false;
export async function bookingDemo(_url: string, _options: RequestInit): Promise<any> { throw new Error("Demo mode is not enabled."); }

import { z } from 'zod';

const envSchema = z.object({
  NEXT_PUBLIC_BACKEND_API_URL: z.string().url(),
});

export function getBackendApiUrl(): string {
  return envSchema.parse({
    NEXT_PUBLIC_BACKEND_API_URL: process.env.NEXT_PUBLIC_BACKEND_API_URL,
  }).NEXT_PUBLIC_BACKEND_API_URL.replace(/\/$/, '');
}

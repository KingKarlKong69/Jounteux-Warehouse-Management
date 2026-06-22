export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role: string;
    profile_photo_url?: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};

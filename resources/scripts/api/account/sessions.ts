import http from '@/api/http';
import useSWR from 'swr';

export interface UserSession {
    uuid: string;
    ipAddress: string | null;
    userAgent: string | null;
    isCurrent: boolean;
    lastUsedAt: Date;
    createdAt: Date | null;
}

export const rawDataToUserSession = (data: any): UserSession => ({
    uuid: data.uuid,
    ipAddress: data.ip_address,
    userAgent: data.user_agent,
    isCurrent: !!data.is_current,
    lastUsedAt: new Date(data.last_used_at),
    createdAt: data.created_at ? new Date(data.created_at) : null,
});

export const useUserSessions = (config?: { revalidateOnMount?: boolean; revalidateOnFocus?: boolean }) =>
    useSWR<UserSession[]>(
        ['/api/client/account/sessions'],
        async () => {
            const { data } = await http.get('/api/client/account/sessions');

            return (data.data || []).map((d: any) => rawDataToUserSession(d.attributes));
        },
        config || { revalidateOnFocus: false }
    );

export const revokeUserSession = async (uuid: string): Promise<void> => {
    await http.delete(`/api/client/account/sessions/${uuid}`);
};

export const revokeOtherUserSessions = async (): Promise<void> => {
    await http.delete('/api/client/account/sessions/other');
};

/**
 * Best-effort user-agent summary for the devices list without adding a parser dependency.
 */
export const describeUserAgent = (userAgent: string | null): string => {
    if (!userAgent) {
        return 'Unknown device';
    }

    let browser = 'Browser';
    if (/Edg\//i.test(userAgent)) {
        browser = 'Edge';
    } else if (/Chrome\//i.test(userAgent) && !/Chromium/i.test(userAgent)) {
        browser = 'Chrome';
    } else if (/Firefox\//i.test(userAgent)) {
        browser = 'Firefox';
    } else if (/Safari\//i.test(userAgent) && !/Chrome\//i.test(userAgent)) {
        browser = 'Safari';
    } else if (/OPR\//i.test(userAgent) || /Opera/i.test(userAgent)) {
        browser = 'Opera';
    }

    let os = 'Unknown OS';
    if (/Windows NT/i.test(userAgent)) {
        os = 'Windows';
    } else if (/Android/i.test(userAgent)) {
        os = 'Android';
    } else if (/iPhone|iPad|iPod/i.test(userAgent)) {
        os = 'iOS';
    } else if (/Mac OS X/i.test(userAgent)) {
        os = 'macOS';
    } else if (/Linux/i.test(userAgent)) {
        os = 'Linux';
    }

    return `${browser} on ${os}`;
};

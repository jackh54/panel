import http from '@/api/http';

export default (servers: string[], offset = 0): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/servers/order', { servers, offset })
            .then(() => resolve())
            .catch(reject);
    });
};

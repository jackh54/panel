import useSWR, { ConfigInterface } from 'swr';
import http, { FractalResponseList } from '@/api/http';
import { Passkey, Transformers } from '@definitions/user';
import { AxiosError } from 'axios';
import { useUserSWRKey } from '@/plugins/useSWRKey';
import { bufferToBase64Url, base64UrlToBuffer } from '@/lib/base64';

const usePasskeys = (config?: ConfigInterface<Passkey[], AxiosError>) => {
    const key = useUserSWRKey(['account', 'passkeys']);

    return useSWR(
        key,
        async () => {
            const { data } = await http.get('/api/client/account/passkeys');

            return (data as FractalResponseList).data.map((datum: any) => {
                return Transformers.toPasskey(datum.attributes);
            });
        },
        { revalidateOnMount: false, ...(config || {}) }
    );
};

const preparePublicKeyOptions = (
    publicKey: any
): PublicKeyCredentialCreationOptions | PublicKeyCredentialRequestOptions => {
    const options = { ...publicKey };

    if (options.challenge) {
        options.challenge = base64UrlToBuffer(options.challenge);
    }

    if (options.user?.id) {
        options.user = {
            ...options.user,
            id: base64UrlToBuffer(options.user.id),
        };
    }

    if (Array.isArray(options.excludeCredentials)) {
        options.excludeCredentials = options.excludeCredentials.map((credential: any) => ({
            ...credential,
            id: base64UrlToBuffer(credential.id),
        }));
    }

    if (Array.isArray(options.allowCredentials)) {
        options.allowCredentials = options.allowCredentials.map((credential: any) => ({
            ...credential,
            id: base64UrlToBuffer(credential.id),
        }));
    }

    return options;
};

const serializeAttestation = (credential: PublicKeyCredential) => {
    const response = credential.response as AuthenticatorAttestationResponse;

    return {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64Url(response.clientDataJSON),
            attestationObject: bufferToBase64Url(response.attestationObject),
        },
        transports: typeof response.getTransports === 'function' ? response.getTransports() : undefined,
    };
};

const serializeAssertion = (credential: PublicKeyCredential) => {
    const response = credential.response as AuthenticatorAssertionResponse;

    return {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64Url(response.clientDataJSON),
            authenticatorData: bufferToBase64Url(response.authenticatorData),
            signature: bufferToBase64Url(response.signature),
            userHandle: response.userHandle ? bufferToBase64Url(response.userHandle) : null,
        },
    };
};

const createPasskey = async (name: string): Promise<Passkey> => {
    if (!window.PublicKeyCredential) {
        throw new Error('Passkeys are not supported in this browser.');
    }

    const { data } = await http.get('/api/client/account/passkeys/options');
    const publicKey = preparePublicKeyOptions(data.data.publicKey) as PublicKeyCredentialCreationOptions;

    const credential = (await navigator.credentials.create({ publicKey })) as PublicKeyCredential | null;
    if (!credential) {
        throw new Error('Passkey registration was cancelled.');
    }

    const { data: created } = await http.post('/api/client/account/passkeys', {
        name,
        token_id: data.data.token_id,
        credential: serializeAttestation(credential),
    });

    return Transformers.toPasskey(created.attributes);
};

const deletePasskey = async (uuid: string): Promise<void> => {
    await http.delete(`/api/client/account/passkeys/${uuid}`);
};

export { usePasskeys, createPasskey, deletePasskey, preparePublicKeyOptions, serializeAssertion };

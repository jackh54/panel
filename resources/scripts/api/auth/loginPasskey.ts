import http from '@/api/http';
import { preparePublicKeyOptions, serializeAssertion } from '@/api/account/passkeys';

export interface PasskeyLoginResponse {
    complete: boolean;
    intended: string | null;
}

export const getPasskeyAuthenticationOptions = async (): Promise<{ tokenId: string; publicKey: any }> => {
    const { data } = await http.get('/auth/passkey/options');

    return {
        tokenId: data.data.token_id,
        publicKey: data.data.publicKey,
    };
};

export const loginWithPasskey = async (): Promise<PasskeyLoginResponse> => {
    if (!window.PublicKeyCredential) {
        throw new Error('Passkeys are not supported in this browser.');
    }

    const { tokenId, publicKey } = await getPasskeyAuthenticationOptions();
    const options = preparePublicKeyOptions(publicKey) as PublicKeyCredentialRequestOptions;

    const credential = (await navigator.credentials.get({ publicKey: options })) as PublicKeyCredential | null;
    if (!credential) {
        throw new Error('Passkey sign-in was cancelled.');
    }

    const response = await http.post('/auth/passkey/verify', {
        token_id: tokenId,
        credential: serializeAssertion(credential),
    });

    return {
        complete: response.data.data?.complete ?? response.data.complete ?? true,
        intended: response.data.data?.intended ?? response.data.intended ?? null,
    };
};

import React, { useEffect, useState } from 'react';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import updateServerOrder from '@/api/updateServerOrder';
import ServerRow from '@/components/dashboard/ServerRow';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { usePersistedState } from '@/plugins/usePersistedState';
import Switch from '@/components/elements/Switch';
import tw from 'twin.macro';
import useSWR from 'swr';
import { PaginatedResult } from '@/api/http';
import Pagination from '@/components/elements/Pagination';
import { useLocation } from 'react-router-dom';

export default () => {
    const { search } = useLocation();
    const defaultPage = Number(new URLSearchParams(search).get('page') || '1');

    const [page, setPage] = useState(!isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const uuid = useStoreState((state) => state.user.data!.uuid);
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
    const [showOnlyAdmin, setShowOnlyAdmin] = usePersistedState(`${uuid}:show_all_servers`, false);
    const [orderedServers, setOrderedServers] = useState<Server[]>([]);
    const [draggingUuid, setDraggingUuid] = useState<string | null>(null);
    const [dropTargetUuid, setDropTargetUuid] = useState<string | null>(null);
    const [savingOrder, setSavingOrder] = useState(false);

    const {
        data: servers,
        error,
        mutate,
    } = useSWR<PaginatedResult<Server>>(['/api/client/servers', showOnlyAdmin && rootAdmin, page], () =>
        getServers({ page, type: showOnlyAdmin && rootAdmin ? 'admin' : undefined })
    );

    useEffect(() => {
        setPage(1);
    }, [showOnlyAdmin]);

    useEffect(() => {
        if (!servers) return;
        setOrderedServers(servers.items);
        if (servers.pagination.currentPage > 1 && !servers.items.length) {
            setPage(1);
        }
    }, [servers]);

    useEffect(() => {
        // Don't use react-router to handle changing this part of the URL, otherwise it
        // triggers a needless re-render. We just want to track this in the URL incase the
        // user refreshes the page.
        window.history.replaceState(null, document.title, `/${page <= 1 ? '' : `?page=${page}`}`);
    }, [page]);

    useEffect(() => {
        if (error) clearAndAddHttpError({ key: 'dashboard', error });
        if (!error) clearFlashes('dashboard');
    }, [error]);

    const persistOrder = (next: Server[]) => {
        setOrderedServers(next);
        setSavingOrder(true);
        const offset = servers ? (servers.pagination.currentPage - 1) * servers.pagination.perPage : 0;
        updateServerOrder(
            next.map((server) => server.uuid),
            offset
        )
            .then(() => mutate())
            .catch((err) => clearAndAddHttpError({ key: 'dashboard', error: err }))
            .finally(() => setSavingOrder(false));
    };

    const onDrop = (targetUuid: string) => {
        if (!draggingUuid || draggingUuid === targetUuid || savingOrder) {
            setDraggingUuid(null);
            setDropTargetUuid(null);
            return;
        }

        const fromIndex = orderedServers.findIndex((server) => server.uuid === draggingUuid);
        const toIndex = orderedServers.findIndex((server) => server.uuid === targetUuid);
        if (fromIndex < 0 || toIndex < 0) {
            setDraggingUuid(null);
            setDropTargetUuid(null);
            return;
        }

        const next = [...orderedServers];
        const [moved] = next.splice(fromIndex, 1);
        next.splice(toIndex, 0, moved);
        setDraggingUuid(null);
        setDropTargetUuid(null);
        persistOrder(next);
    };

    const canReorder = !showOnlyAdmin && orderedServers.length > 1;

    return (
        <PageContentBlock title={'Dashboard'} showFlashKey={'dashboard'}>
            {rootAdmin && (
                <div css={tw`mb-2 flex justify-end items-center`}>
                    <p css={tw`uppercase text-xs text-neutral-400 mr-2`}>
                        {showOnlyAdmin ? "Showing others' servers" : 'Showing your servers'}
                    </p>
                    <Switch
                        name={'show_all_servers'}
                        defaultChecked={showOnlyAdmin}
                        onChange={() => setShowOnlyAdmin((s) => !s)}
                    />
                </div>
            )}
            {canReorder && (
                <p css={tw`mb-2 text-xs text-neutral-500`}>
                    Drag a server card to reorder
                    {savingOrder ? ' — saving…' : ''}
                </p>
            )}
            {!servers ? (
                <Spinner centered size={'large'} />
            ) : (
                <Pagination data={servers} onPageSelect={setPage}>
                    {() =>
                        orderedServers.length > 0 ? (
                            orderedServers.map((server, index) => (
                                <div
                                    key={server.uuid}
                                    css={index > 0 ? tw`mt-2` : undefined}
                                    onDragOver={(event) => {
                                        if (!canReorder || !draggingUuid) return;
                                        event.preventDefault();
                                        if (dropTargetUuid !== server.uuid) {
                                            setDropTargetUuid(server.uuid);
                                        }
                                    }}
                                    onDragLeave={() => {
                                        if (dropTargetUuid === server.uuid) {
                                            setDropTargetUuid(null);
                                        }
                                    }}
                                    onDrop={(event) => {
                                        if (!canReorder) return;
                                        event.preventDefault();
                                        onDrop(server.uuid);
                                    }}
                                >
                                    <ServerRow
                                        server={server}
                                        draggable={canReorder}
                                        onDragStart={() => setDraggingUuid(server.uuid)}
                                        onDragEnd={() => {
                                            setDraggingUuid(null);
                                            setDropTargetUuid(null);
                                        }}
                                        isDragging={draggingUuid === server.uuid}
                                        isDropTarget={dropTargetUuid === server.uuid}
                                    />
                                </div>
                            ))
                        ) : (
                            <p css={tw`text-center text-sm text-neutral-400`}>
                                {showOnlyAdmin
                                    ? 'There are no other servers to display.'
                                    : 'There are no servers associated with your account.'}
                            </p>
                        )
                    }
                </Pagination>
            )}
        </PageContentBlock>
    );
};

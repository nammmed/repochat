import React, {useEffect, useState} from "react";
import MainRow from "./mainRow";
import ExpandedRow from "./expandedRow";
import DNSModal from "./DNSModal";
import {toast} from "react-toastify";
import {
    addSiteToCloudflare,
    disableCloudflareSSL,
    getCloudflareNSServers,
    getCloudflareZonesList,
    importCloudflareDNSRecords,
    removeSiteFromCloudflare,
    saveCloudflareCredentials,
    setCloudflareECHStatus,
    setCloudflareSSLMode,
    setCloudflareTLS13Status
} from "../../API/cloudflare";
import NSModal from "./NSModal";
import DeleteConfirmModal from "./deleteConfirmModal";

const CloudflareListTr = ({site}) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [zones, setZones] = useState([]);
    const [selectedZone, setSelectedZone] = useState(null);
    const [showDnsModal, setShowDnsModal] = useState(false);
    const [showNsModal, setShowNsModal] = useState(false);
    const [nsServers, setNsServers] = useState([]);
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const [loadingStates, setLoadingStates] = useState({
        isImportingDNS: null,
        isAddingSite: false,
        isTogglingSSL: null,
        isTogglingECH: null,
        isTogglingTLS: null,
        isGettingNS: null,
        isSavingCredentials: false,
        isLoadingZones: false,
        isRemovingZone: null
    });

    useEffect(() => {
        if (isExpanded && zones.length === 0) {
            if (site.cf_email && site.cf_api_key) {
                handleLoadZones();
            }
        }
    }, [isExpanded, site.cf_email, site.cf_api_key]);

    const handleSaveCredentials = async (email, apiKey) => {
        try {
            setLoadingStates(prev => ({...prev, isSavingCredentials: true}));
            await saveCloudflareCredentials(site.id, email, apiKey);
            toast.success('Учетные данные сохранены');
            await handleLoadZones();
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isSavingCredentials: false}));
        }
    };

    const handleLoadZones = async () => {
        setLoadingStates(prev => ({...prev, isLoadingZones: true}));
        try {
            const {zones} = await getCloudflareZonesList(site.id);
            setZones(zones);
        } catch (error) {
            toast.error(`Ошибка загрузки списка зон: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isLoadingZones: false}));
        }
    };

    const handleAddDomain = async (domain) => {
        try {
            setLoadingStates(prev => ({...prev, isAddingSite: true}));
            const response = await addSiteToCloudflare(site.id, domain);

            // Берем только нужные нам поля из ответа
            const newZone = {
                id: response.zone.id,
                name: response.zone.name,
                status: response.zone.status,
                ssl: response.zone.ssl || 'flexible', // используем значение из ответа или дефолтное
                ech: response.zone.ech || 'off',
                tls13: response.zone.tls13 || 'off'
            };

            // Добавляем новую зону в существующий список с сортировкой
            setZones(prevZones => [...prevZones, newZone]
                .sort((a, b) => a.name.localeCompare(b.name)));

            toast.success('Домен успешно добавлен');
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isAddingSite: false}));
        }
    };

    const handleToggleSSL = async (zoneId) => {
        try {
            setLoadingStates(prev => ({...prev, isTogglingSSL: zoneId}));
            const zone = zones.find(z => z.id === zoneId);

            let response;
            if (zone.ssl !== 'off') {
                response = await disableCloudflareSSL(site.id, zoneId);
            } else {
                response = await setCloudflareSSLMode(site.id, zoneId, 'flexible');
            }
            setZones(prevZones => prevZones.map(z =>
                z.id === zoneId ? {...z, ssl: response.result.value} : z
            ));
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isTogglingSSL: null}));
        }
    };

    const handleToggleECH = async (zoneId) => {
        try {
            setLoadingStates(prev => ({...prev, isTogglingECH: zoneId}));
            const zone = zones.find(z => z.id === zoneId);
            const newValue = zone.ech === 'on' ? 'off' : 'on';

            const response = await setCloudflareECHStatus(site.id, zoneId, newValue);

            setZones(prevZones => prevZones.map(z =>
                z.id === zoneId ? {...z, ech: response.result.value} : z
            ));
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isTogglingECH: null}));
        }
    };

    const handleToggleTLS = async (zoneId) => {
        try {
            setLoadingStates(prev => ({...prev, isTogglingTLS: zoneId}));
            const zone = zones.find(z => z.id === zoneId);
            const newValue = zone.tls13 === 'on' ? 'off' : 'on';

            const response = await setCloudflareTLS13Status(site.id, zoneId, newValue);

            setZones(prevZones => prevZones.map(z =>
                z.id === zoneId ? {...z, tls13: response.result.value} : z
            ));
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isTogglingTLS: null}));
        }
    };

    const handleGetNS = async (zoneId) => {
        try {
            setLoadingStates(prev => ({...prev, isGettingNS: zoneId}));
            const zone = zones.find(z => z.id === zoneId);
            const data = await getCloudflareNSServers(site.id, zoneId);
            setNsServers(data.name_servers);
            setSelectedZone(zone);
            setShowNsModal(true);
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isGettingNS: null}));
        }
    };

    const handleShowDnsModal = (zoneId) => {
        const zone = zones.find(z => z.id === zoneId);
        setSelectedZone(zone);
        setShowDnsModal(true);
    };

    const handleImportDNS = async (dnsFile) => {
        try {
            setLoadingStates(prev => ({...prev, isImportingDNS: selectedZone.id}));
            await importCloudflareDNSRecords(site.id, selectedZone.id, dnsFile);
            toast.success('DNS записи импортированы');
            setShowDnsModal(false);
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isImportingDNS: null}));
            setSelectedZone(null);
        }
    };

    const handleRemoveZone = async (zoneId) => {
        const zone = zones.find(z => z.id === zoneId);
        setSelectedZone(zone);
        setShowDeleteModal(true);
    };

    const handleConfirmDelete = async () => {
        try {
            setLoadingStates(prev => ({...prev, isRemovingZone: selectedZone.id}));
            await removeSiteFromCloudflare(site.id, selectedZone.id);
            await handleLoadZones();
            toast.success('Сайт успешно удален из Cloudflare');
            setShowDeleteModal(false);
            setSelectedZone(null);
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setLoadingStates(prev => ({...prev, isRemovingZone: null}));
        }
    };

    return (
        <>
            <MainRow
                site={site}
                isExpanded={isExpanded}
                setIsExpanded={setIsExpanded}
            />

            {isExpanded && (
                <ExpandedRow
                    site={site}
                    isLoading={isLoading}
                    loadingStates={loadingStates}
                    onSaveCredentials={handleSaveCredentials}
                    onToggleSSL={handleToggleSSL}
                    onToggleECH={handleToggleECH}
                    onToggleTLS={handleToggleTLS}
                    onShowDnsModal={handleShowDnsModal}
                    onGetNS={handleGetNS}
                    onAddDomain={handleAddDomain}
                    onRemoveZone={handleRemoveZone}
                    onLoadZones={handleLoadZones}
                    zones={zones}
                />
            )}

            <DNSModal
                show={showDnsModal}
                onHide={() => {
                    setShowDnsModal(false);
                    setSelectedZone(null);
                }}
                onImport={handleImportDNS}
                isImporting={loadingStates.isImportingDNS === selectedZone?.id}
                domain={selectedZone?.name}
            />

            <NSModal
                show={showNsModal}
                onHide={() => {
                    setShowNsModal(false);
                    setSelectedZone(null);
                    setNsServers([]);
                }}
                nsServers={nsServers}
                domain={selectedZone?.name}
            />

            <DeleteConfirmModal
                show={showDeleteModal}
                onHide={() => {
                    setShowDeleteModal(false);
                    setSelectedZone(null);
                }}
                onConfirm={handleConfirmDelete}
                domain={selectedZone?.name}
                isLoading={loadingStates.isRemovingZone === selectedZone?.id}
            />

        </>
    );
};

export default CloudflareListTr;
import {SERVER_URL} from "../config";

// Базовая функция для запросов
async function fetchCloudflare(action, data) {
    const response = await fetch(SERVER_URL + 'actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action,
            ...data,
        }),
        credentials: 'include',
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Network response was not ok');
    }
    return response.json();
}

// Управление сайтом
export const addSiteToCloudflare = (siteId, domain) =>
    fetchCloudflare('addSiteToCloudflare', {siteId, domain});

export const removeSiteFromCloudflare = (siteId, zoneId) =>
    fetchCloudflare('removeSiteFromCloudflare', {siteId, zoneId});

// Управление DNS
export const importCloudflareDNSRecords = (siteId, zoneId, file) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'importCloudflareDNSRecords');
    formData.append('siteId', siteId);
    formData.append('zoneId', zoneId);

    return fetch(SERVER_URL + 'actions.php', {
        method: 'POST',
        body: formData,
        credentials: 'include',
    }).then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.error || 'Network response was not ok');
            });
        }
        return response.json();
    });
};

export const getCloudflareNSServers = (siteId, zoneId) =>
    fetchCloudflare('getCloudflareNSServers', {siteId, zoneId});

// Управление SSL
export const setCloudflareSSLMode = (siteId, zoneId, mode) =>
    fetchCloudflare('setCloudflareSSLMode', {siteId, zoneId, mode});

export const disableCloudflareSSL = (siteId, zoneId) =>
    fetchCloudflare('setCloudflareSSLMode', {siteId, zoneId, mode: 'off'});

// Управление ECH
export const setCloudflareECHStatus = (siteId, zoneId, value) =>
    fetchCloudflare('setCloudflareECHStatus', {siteId, zoneId, value});

// Управление TLS 1.3
export const setCloudflareTLS13Status = (siteId, zoneId, value) =>
    fetchCloudflare('setCloudflareTLS13Status', {siteId, zoneId, value});

// Управление учетными данными
export const saveCloudflareCredentials = (siteId, cf_email, cf_api_key) =>
    fetchCloudflare('saveCloudflareCredentials', {siteId, cf_email, cf_api_key});

// Получение списка зон
export const getCloudflareZonesList = (siteId) =>
    fetchCloudflare('getCloudflareZonesList', {siteId});

// Получение DNS-записей для заданной зоны
export const getDNSRecords = (siteId, zoneId) =>
    fetchCloudflare('getDNSRecords', {siteId, zoneId});

// Обновление DNS-записей зоны
export const updateDNSRecord = (siteId, zoneId, recordId, content, proxied) =>
    fetchCloudflare('updateDNSRecord', {siteId, zoneId, recordId, content, proxied});

export const updateDNSRecords = (siteId, zoneId, content, proxied) =>
    fetchCloudflare('updateDNSRecords', { siteId, zoneId, content, proxied });

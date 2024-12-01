import React, {useState} from 'react';
import LoadingButton from './loadingButtons';
import {toast} from "react-toastify";
import {getDNSRecords, updateDNSRecords} from '../../API/cloudflare';
import {Button, Form, InputGroup} from "react-bootstrap";

const ActionButtons = ({
                           zone,
                           onToggleSSL,
                           onToggleECH,
                           onToggleTLS,
                           onShowDnsModal,
                           onGetNS,
                           onRemoveZone,
                           loadingStates
                       }) => {
    const [dnsDataLoaded, setDnsDataLoaded] = useState(false);
    const [ipAddress, setIpAddress] = useState('');
    const [proxied, setProxied] = useState(false);
    const [isLoadingDNS, setIsLoadingDNS] = useState(false);
    const [isUpdatingDNS, setIsUpdatingDNS] = useState(false);
    const [mostUsedIP, setMostUsedIP] = useState(null);

    const fetchDNSRecords = async () => {
        if (dnsDataLoaded) {
            setDnsDataLoaded(false);
            return;
        }

        setIsLoadingDNS(true);
        try {
            const response = await getDNSRecords(zone.siteId, zone.id);
            const records = response.dns_records;

            if (records.length === 0) {
                toast.error('DNS записи не найдены');
                return;
            }

            // Используем значения первой записи
            setIpAddress(records[0].content);
            setProxied(records[0].proxied);
            setDnsDataLoaded(true);
            setMostUsedIP(response.most_used_ip);
        } catch (error) {
            toast.error(`Ошибка при загрузке DNS записей: ${error.message}`);
        } finally {
            setIsLoadingDNS(false);
        }
    };

    const handleUpdateRecords = async () => {
        // Проверяем корректность IP-адреса
        const ipRegex = /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;
        if (!ipRegex.test(ipAddress)) {
            toast.error('Введите корректный IP-адрес');
            return;
        }
        setIsUpdatingDNS(true);
        try {
            await updateDNSRecords(
                zone.siteId,
                zone.id,
                ipAddress,
                proxied
            );
            toast.success('DNS записи обновлены успешно');
        } catch (error) {
            toast.error(`Ошибка при обновлении DNS записей: ${error.message}`);
        } finally {
            setIsUpdatingDNS(false);
        }
    };

    if (!zone) {
        return null;
    }
    return (
        <div className="d-flex flex-wrap gap-2">
            <LoadingButton
                size="sm"
                isLoading={loadingStates.isTogglingSSL === zone.id}
                variant={zone.ssl !== 'off' ? 'danger' : 'success'}
                onClick={() => onToggleSSL(zone.id)}
            >
                {zone.ssl !== 'off' ? 'Отключить SSL' : 'Включить SSL'}
            </LoadingButton>

            <LoadingButton
                size="sm"
                isLoading={loadingStates.isTogglingECH === zone.id}
                variant={zone.ech === 'on' ? 'danger' : 'success'}
                onClick={() => onToggleECH(zone.id)}
            >
                {zone.ech === 'on' ? 'Отключить' : 'Включить'} ECH
            </LoadingButton>

            <LoadingButton
                size="sm"
                isLoading={loadingStates.isTogglingTLS === zone.id}
                variant={zone.tls13 === 'on' ? 'danger' : 'success'}
                onClick={() => onToggleTLS(zone.id)}
            >
                {zone.tls13 === 'on' ? 'Отключить' : 'Включить'} TLS 1.3
            </LoadingButton>

            <LoadingButton
                size="sm"
                isLoading={loadingStates.isImportingDNS === zone.id}
                variant="primary"
                onClick={() => onShowDnsModal(zone.id)}
            >
                Импорт DNS
            </LoadingButton>

            <LoadingButton
                size="sm"
                isLoading={loadingStates.isGettingNS === zone.id}
                variant="primary"
                onClick={() => onGetNS(zone.id)}
            >
                Получить NS
            </LoadingButton>

            <Button
                size="sm"
                variant="danger"
                onClick={() => onRemoveZone(zone.id)}
            >
                Удалить
            </Button>

            <Button
                size="sm"
                variant="info"
                onClick={fetchDNSRecords}
                disabled={isLoadingDNS}
            >
                {isLoadingDNS ? 'Загрузка...' : dnsDataLoaded ? 'Закрыть управление DNS' : 'Управлять DNS'}
            </Button>

            {dnsDataLoaded && (
                <div style={{width: '100%', marginTop: '10px'}}>
                    <Form.Group controlId={`ipAddress-${zone.id}`} className="mb-2">
                        <Form.Label>IP адрес</Form.Label>
                        <div className="d-flex align-items-center">
                            <Form.Control
                                type="text"
                                value={ipAddress}
                                onChange={(e) => setIpAddress(e.target.value)}
                            />
                            {mostUsedIP && ipAddress !== mostUsedIP && (
                                <span
                                    className="ms-2 badge rounded-pill bg-secondary"
                                    style={{cursor: 'pointer'}}
                                    onClick={() => setIpAddress(mostUsedIP)}
                                >{mostUsedIP}</span>
                            )}
                        </div>
                    </Form.Group>
                    <Form.Group controlId={`proxied-${zone.id}`} className="mb-2">
                        <Form.Check
                            type="switch"
                            label="Проксирование (Cloudflare)"
                            checked={proxied}
                            onChange={(e) => setProxied(e.target.checked)}
                        />
                    </Form.Group>
                    <Button
                        variant="primary"
                        onClick={handleUpdateRecords}
                        disabled={isUpdatingDNS}
                    >
                        {isUpdatingDNS ? 'Сохранение...' : 'Сохранить'}
                    </Button>
                </div>
            )}
        </div>
    );
};

export default ActionButtons;
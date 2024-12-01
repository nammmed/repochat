import React, {useState, useEffect} from 'react';
import {Table, Button, Form, Spinner} from 'react-bootstrap';
import {getDNSRecords, updateDNSRecord} from '../../API/cloudflare';
import {toast} from 'react-toastify';

const DNSRecords = ({siteId, zoneId}) => {
    const [dnsRecords, setDNSRecords] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [updatingRecordId, setUpdatingRecordId] = useState(null);

    const fetchDNSRecords = async () => {
        setIsLoading(true);
        try {
            const response = await getDNSRecords(siteId, zoneId);
            setDNSRecords(response.dns_records);
        } catch (error) {
            toast.error(`Ошибка при загрузке DNS записей: ${error.message}`);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchDNSRecords();
    }, [siteId, zoneId]);

    const handleUpdateRecord = async (record) => {
        setUpdatingRecordId(record.id);
        try {
            await updateDNSRecord(siteId, zoneId, record.id, record.content, record.proxied);
            toast.success(`Запись ${record.name} обновлена успешно`);
            fetchDNSRecords(); // Обновляем список записей
        } catch (error) {
            toast.error(`Ошибка при обновлении записи: ${error.message}`);
        } finally {
            setUpdatingRecordId(null);
        }
    };

    return (
        <div className="mt-3">
            <h6>Управление DNS-записями</h6>
            {isLoading ? (
                <div className="text-center">
                    <Spinner animation="border" role="status"/>
                    <p className="mt-2">Загрузка DNS записей...</p>
                </div>
            ) : (
                <Table responsive bordered>
                    <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Тип</th>
                        <th>IP-адрес</th>
                        <th>Проксирование</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    {dnsRecords.map(record => (
                        <tr key={record.id}>
                            <td>{record.name}</td>
                            <td>{record.type}</td>
                            <td>
                                <Form.Control
                                    type="text"
                                    defaultValue={record.content}
                                    onChange={(e) => {
                                        const newContent = e.target.value;
                                        setDNSRecords(prevRecords =>
                                            prevRecords.map(r =>
                                                r.id === record.id
                                                    ? {...r, content: newContent}
                                                    : r
                                            )
                                        );
                                    }}
                                />
                            </td>
                            <td className="text-center">
                                <Form.Check
                                    type="switch"
                                    id={`proxied-switch-${record.id}`}
                                    checked={record.proxied}
                                    onChange={(e) => {
                                        const newProxied = e.target.checked;
                                        setDNSRecords(prevRecords =>
                                            prevRecords.map(r =>
                                                r.id === record.id
                                                    ? {...r, proxied: newProxied}
                                                    : r
                                            )
                                        );
                                    }}
                                />
                            </td>
                            <td>
                                <Button
                                    variant="primary"
                                    size="sm"
                                    disabled={updatingRecordId === record.id}
                                    onClick={() => handleUpdateRecord(record)}
                                >
                                    {updatingRecordId === record.id ? 'Обновление...' : 'Сохранить'}
                                </Button>
                            </td>
                        </tr>
                    ))}
                    </tbody>
                </Table>
            )}
        </div>
    );
};

export default DNSRecords;
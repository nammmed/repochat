import React, { useState } from 'react';
import { Button, Spinner } from 'react-bootstrap';
import { toast } from 'react-toastify';

const BulkActions = ({ zones, onToggleSSL, onToggleECH, onToggleTLS }) => {
    const [isBulkProcessing, setIsBulkProcessing] = useState(false);
    const [currentAction, setCurrentAction] = useState('');

    const processAll = async (zones, action, targetValue) => {
        const actionName = targetValue === 'on' || targetValue === 'flexible' ?
            `Включение ${action}` :
            `Отключение ${action}`;

        setIsBulkProcessing(true);
        setCurrentAction(actionName);

        try {
            const zonesToProcess = zones.filter(zone => {
                switch (action) {
                    case 'SSL':
                        return targetValue === 'flexible' ?
                            zone.ssl === 'off' :
                            zone.ssl !== 'off';
                    case 'ECH':
                        return zone.ech !== targetValue;
                    case 'TLS 1.3':
                        return zone.tls13 !== targetValue;
                    default:
                        return false;
                }
            });

            if (zonesToProcess.length === 0) {
                toast.info('Нет зон, требующих изменений');
                return;
            }

            for (const zone of zonesToProcess) {
                try {
                    switch (action) {
                        case 'SSL':
                            await onToggleSSL(zone.id);
                            break;
                        case 'ECH':
                            await onToggleECH(zone.id);
                            break;
                        case 'TLS 1.3':
                            await onToggleTLS(zone.id);
                            break;
                    }
                } catch (error) {
                    toast.error(`Ошибка при обработке ${zone.name}: ${error.message}`);
                }
            }

            toast.success(`${actionName} выполнено успешно`);
        } catch (error) {
            toast.error(`Ошибка при выполнении ${actionName.toLowerCase()}`);
        } finally {
            setIsBulkProcessing(false);
            setCurrentAction('');
        }
    };

    const handleBulkSSL = (enable) => {
        processAll(zones, 'SSL', enable ? 'flexible' : 'off');
    };

    const handleBulkECH = (enable) => {
        processAll(zones, 'ECH', enable ? 'on' : 'off');
    };

    const handleBulkTLS = (enable) => {
        processAll(zones, 'TLS 1.3', enable ? 'on' : 'off');
    };

    return (
        <div className="mb-3 p-3 border rounded">
            <h6 className="mb-3">Групповые действия</h6>
            <div className="d-flex gap-2">
                <Button
                    variant="outline-primary"
                    onClick={() => handleBulkSSL(true)}
                    disabled={isBulkProcessing}
                >
                    Включить SSL
                </Button>
                <Button
                    variant="outline-danger"
                    onClick={() => handleBulkSSL(false)}
                    disabled={isBulkProcessing}
                >
                    Отключить SSL
                </Button>

                <Button
                    variant="outline-primary"
                    onClick={() => handleBulkECH(true)}
                    disabled={isBulkProcessing}
                >
                    Включить ECH
                </Button>
                <Button
                    variant="outline-danger"
                    onClick={() => handleBulkECH(false)}
                    disabled={isBulkProcessing}
                >
                    Отключить ECH
                </Button>

                <Button
                    variant="outline-primary"
                    onClick={() => handleBulkTLS(true)}
                    disabled={isBulkProcessing}
                >
                    Включить TLS 1.3
                </Button>
                <Button
                    variant="outline-danger"
                    onClick={() => handleBulkTLS(false)}
                    disabled={isBulkProcessing}
                >
                    Отключить TLS 1.3
                </Button>
            </div>
            {isBulkProcessing && (
                <div className="mt-2">
                    <Spinner size="sm" animation="border" className="me-2" />
                    {currentAction}...
                </div>
            )}
        </div>
    );
};

export default BulkActions;
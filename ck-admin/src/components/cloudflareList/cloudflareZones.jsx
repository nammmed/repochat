import React, {useState} from "react";
import {Form, Table, Spinner} from 'react-bootstrap';
import LoadingButton from "./loadingButtons";
import ActionButtons from "./actionButtons";
import BulkActions from "./bulkActions";

const CloudflareZones = ({
                             onAddDomain,
                             isLoadingZones,
                             onLoadZones,
                             loadingStates,
                             siteId,
                             zones = [],
                             ...actionProps
                         }) => {
    const [newDomain, setNewDomain] = useState('');

    const sortedZones = [...zones].sort((a, b) => a.name.localeCompare(b.name));

    return (
        <div className="mb-3">
            {/* Форма добавления нового домена */}
            <Form className="mb-3">
                <Form.Group className="mb-2">
                    <Form.Label>Добавить новый домен в Cloudflare</Form.Label>
                    <div className="d-flex gap-2">
                        <Form.Control
                            type="text"
                            value={newDomain}
                            onChange={(e) => setNewDomain(e.target.value)}
                            placeholder="example.com"
                        />
                        <LoadingButton
                            onClick={() => {
                                onAddDomain(newDomain);
                                setNewDomain('');
                            }}
                            disabled={!newDomain}
                            isLoading={loadingStates.isAddingSite}
                        >
                            Добавить
                        </LoadingButton>
                    </div>
                </Form.Group>
            </Form>

            <BulkActions
                zones={sortedZones}
                onToggleSSL={actionProps.onToggleSSL}
                onToggleECH={actionProps.onToggleECH}
                onToggleTLS={actionProps.onToggleTLS}
            />

            <div className="d-flex justify-content-between align-items-center mb-2">
                <h6 className="mb-0">Сайты в Cloudflare:</h6>
                <LoadingButton
                    variant="secondary"
                    size="sm"
                    isLoading={isLoadingZones}
                    onClick={onLoadZones}
                >
                    Обновить список
                </LoadingButton>
            </div>

            {isLoadingZones ? (
                <div className="text-center">
                    <Spinner animation="border" role="status"/>
                    <p className="mt-2">Загрузка списка доменов...</p>
                </div>
            ) : (
                sortedZones.length > 0 ? (
                    <Table striped bordered hover>
                        <thead>
                        <tr>
                            <th>Домен</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        {sortedZones.map(zone => (
                            <tr key={zone.id}>
                                <td>{zone.name}</td>
                                <td>{zone.status}</td>
                                <td>
                                    <ActionButtons
                                        zone={{...zone, siteId}}
                                        loadingStates={loadingStates}
                                        onToggleSSL={actionProps.onToggleSSL}
                                        onToggleECH={actionProps.onToggleECH}
                                        onToggleTLS={actionProps.onToggleTLS}
                                        onShowDnsModal={actionProps.onShowDnsModal}
                                        onGetNS={actionProps.onGetNS}
                                        onRemoveZone={actionProps.onRemoveZone}
                                    />
                                </td>
                            </tr>
                        ))}
                        </tbody>
                    </Table>
                ) : (
                    <div className="text-muted">Нет добавленных сайтов</div>
                )
            )}
        </div>
    );
};

export default CloudflareZones;
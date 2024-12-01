import React from 'react';
import CredentialsForm from './credentialsForm';
import ActionButtons from './actionButtons';
import {Spinner} from 'react-bootstrap';
import CloudflareZones from "./cloudflareZones";

const ExpandedRow = ({
                         site,
                         isLoading,
                         settings,
                         loadingStates,
                         onSaveCredentials,
                         onToggleSSL,
                         onToggleECH,
                         onToggleTLS,
                         onShowDnsModal,
                         onGetNS,
                         onAddSite,
                         onAddDomain,
                         onRemoveZone,
                         onLoadZones,
                         zones,
                     }) => {
    if (isLoading) {
        return (
            <tr>
                <td colSpan="5" className="text-center">
                    <Spinner animation="border" role="status">
                        <span className="visually-hidden">Загрузка...</span>
                    </Spinner>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td colSpan="5">
                <div className="p-3">
                    <CredentialsForm
                        site={site}
                        onSave={onSaveCredentials}
                    />

                    <CloudflareZones
                        onAddDomain={onAddDomain}
                        onRemoveZone={onRemoveZone}
                        isLoadingZones={loadingStates.isLoadingZones}
                        onLoadZones={onLoadZones}
                        loadingStates={loadingStates}
                        zones={zones}
                        onToggleSSL={onToggleSSL}
                        onToggleECH={onToggleECH}
                        onToggleTLS={onToggleTLS}
                        onShowDnsModal={onShowDnsModal}
                        onGetNS={onGetNS}
                        siteId={site.id}
                    />

                </div>
            </td>
        </tr>
    );
};

export default ExpandedRow;
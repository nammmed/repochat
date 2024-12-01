import React from 'react';
import {Button} from "react-bootstrap";

const MainRow = ({ site, isExpanded, setIsExpanded }) => {
    return (
        <tr onClick={() => setIsExpanded(!isExpanded)} style={{ cursor: 'pointer' }}>
            <td>{site.primaryDomain}</td>
            <td>{site.config_primary}</td>
            <td>{site.cf_email}</td>
            <td>{site.cf_api_key ? '********' : ''}</td>
            <td>
                <Button variant="link" size="sm">
                    {isExpanded ? 'Скрыть' : 'Показать'}
                </Button>
            </td>
        </tr>
    );
};

export default MainRow;
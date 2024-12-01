import React, {useEffect, useState} from 'react';
import {Card, Table} from 'react-bootstrap';
import {useAppContext} from "../../context/appContext";
import {getSites} from "../../API/sites";
import CloudflareListTr from "./cloudflareListTr";
import {toast} from "react-toastify";

const CloudflareList = () => {
    const [sites, setSites] = useState([]);
    const {setIsLoading, error, setError} = useAppContext();

    const fetchSites = () => {
        setIsLoading(true);
        getSites()
            .then(sites => {
                setSites(sites);
            })
            .catch(error => {
                setError(error.toString())
                toast.error(`Ошибка при загрузке сайтов: ${error.message}`);
            })
            .finally(() => {
                setIsLoading(false);
            })
    };

    useEffect(fetchSites, [setIsLoading, setError]);

    return (
        <Card className="mb-5 mt-5">
            <Card.Header><b>Управление Cloudflare</b></Card.Header>
            <Card.Body>
                <Table responsive hover>
                    <thead>
                    <tr>
                        <th>Сайт</th>
                        <th>Текущий домен</th>
                        <th>Cloudflare Email</th>
                        <th>Cloudflare API Key</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    {error
                        ? <tr>
                            <td colSpan="5">{error}</td>
                        </tr>
                        : (!sites.length
                                ? <tr>
                                    <td colSpan="5">Нет сайтов для отображения</td>
                                </tr>
                                : sites.map((site) => (
                                    <CloudflareListTr key={site.id} site={site} />
                                ))
                        )
                    }
                    </tbody>
                </Table>
            </Card.Body>
        </Card>
    );
};

export default CloudflareList;
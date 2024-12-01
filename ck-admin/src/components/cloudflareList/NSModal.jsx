import React from 'react';
import {Modal} from 'react-bootstrap';
import {toast} from "react-toastify";

const NSModal = ({
                     show,
                     onHide,
                     nsServers,
                     domain
                 }) => {

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text)
            .then(() => {
                toast.success(`Скопировано: ${text}`, {
                    autoClose: 500,
                });
            })
            .catch((err) => {
                toast.error('Ошибка копирования в буфер обмена', {
                    autoClose: 2000,
                });
                console.error('Ошибка копирования в буфер обмена: ', err);
            });
    };

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>NS сервера для {domain}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <ul className="list-unstyled mb-0">
                    {nsServers.map((ns, index) => (
                        <li
                            key={index}
                            onClick={() => copyToClipboard(ns)}
                            style={{cursor: 'pointer'}}
                        >
                            <span class="me-2">{ns}</span>
                        </li>
                    ))}
                </ul>
            </Modal.Body>
        </Modal>
    );
};

export default NSModal;
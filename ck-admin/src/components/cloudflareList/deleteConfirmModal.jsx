import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import LoadingButton from "./loadingButtons";

const DeleteConfirmModal = ({show, onHide, onConfirm, domain, isLoading}) => {
    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>Подтверждение удаления</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                Вы действительно хотите удалить домен <strong>{domain}</strong> из Cloudflare?
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide}>
                    Отмена
                </Button>
                <LoadingButton
                    variant="danger"
                    onClick={onConfirm}
                    isLoading={isLoading}
                >
                    Удалить
                </LoadingButton>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteConfirmModal;
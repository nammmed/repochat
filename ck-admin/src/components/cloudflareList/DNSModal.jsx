import React, {useState} from 'react';
import {Modal, Button, Form, Spinner} from 'react-bootstrap';

const DNSModal = ({
                      show,
                      onHide,
                      onImport,
                      isImporting,
                      domain
                  }) => {
    const [dnsFile, setDnsFile] = useState(null);

    const handleFileChange = (e) => {
        if (e.target.files.length > 0) {
            setDnsFile(e.target.files[0]);
        }
    };

    const handleImport = () => {
        onImport(dnsFile);
    };

    const handleClose = () => {
        setDnsFile(null);
        onHide();
    };

    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Импорт DNS записей для {domain}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Form.Group>
                    <Form.Label>Выберите файл с DNS записями (BIND формат)</Form.Label>
                    <Form.Control
                        type="file"
                        onChange={handleFileChange}
                        accept=".txt,.bind,.zone"
                    />
                    <Form.Text className="text-muted">
                        Файл должен содержать DNS записи в формате BIND
                    </Form.Text>
                </Form.Group>
            </Modal.Body>
            <Modal.Footer>
                <Button
                    variant="secondary"
                    onClick={handleClose}
                    disabled={isImporting}
                >
                    Отмена
                </Button>
                <Button
                    variant="primary"
                    onClick={handleImport}
                    disabled={!dnsFile || isImporting}
                >
                    {isImporting ? (
                        <>
                            <Spinner
                                as="span"
                                animation="border"
                                size="sm"
                                role="status"
                                aria-hidden="true"
                                className="me-2"
                            />
                            Импорт...
                        </>
                    ) : 'Импортировать'}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DNSModal;
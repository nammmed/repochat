import React, { useState } from "react";
import { Form, Row, Col } from 'react-bootstrap';
import LoadingButton from "./loadingButtons";
import { toast } from "react-toastify";

const CredentialsForm = ({ site, onSave }) => {
    const [cfEmail, setCfEmail] = useState(site.cf_email || '');
    const [cfApiKey, setCfApiKey] = useState(site.cf_api_key || '');
    const [isEditing, setIsEditing] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleSubmit = async () => {
        setIsSaving(true);
        try {
            await onSave(cfEmail, cfApiKey);
            setIsEditing(false);
        } catch (error) {
            toast.error(`Ошибка: ${error.message}`);
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <Form className="mb-3">
            <Form.Group as={Row} className="align-items-center">
                <Col xs="auto">
                    <Form.Label className="me-2">Email</Form.Label>
                </Col>
                <Col>
                    <Form.Control
                        type="email"
                        value={cfEmail}
                        onChange={(e) => setCfEmail(e.target.value)}
                    />
                </Col>
                <Col xs="auto">
                    <Form.Label className="me-2">API Key</Form.Label>
                </Col>
                <Col>
                    <Form.Control
                        type={isEditing ? 'text' : 'password'}
                        value={cfApiKey}
                        onChange={(e) => setCfApiKey(e.target.value)}
                        onFocus={() => setIsEditing(true)}
                    />
                </Col>
                <Col xs="auto">
                    <LoadingButton
                        isLoading={isSaving}
                        onClick={handleSubmit}
                    >
                        Сохранить
                    </LoadingButton>
                </Col>
            </Form.Group>
        </Form>
    );
};

export default CredentialsForm;
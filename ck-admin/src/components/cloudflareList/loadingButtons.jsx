import {Spinner, Button} from 'react-bootstrap';
import React from "react";

const LoadingButton = ({isLoading, onClick, variant, children}) => (
    <Button
        variant={variant}
        onClick={onClick}
        disabled={isLoading}
        style={{whiteSpace: 'nowrap'}}
    >
        {isLoading ? (
            <>
                <Spinner
                    as="span"
                    animation="border"
                    size="sm"
                    role="status"
                    aria-hidden="true"
                    className="me-2"
                />
                Загрузка...
            </>
        ) : children}
    </Button>
);

export default LoadingButton;
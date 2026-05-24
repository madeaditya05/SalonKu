import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App.jsx';
import { CustomerLanguageProvider } from './i18n.jsx';
import './styles.css';

createRoot(document.getElementById('customer-landing-root')).render(
    <React.StrictMode>
        <CustomerLanguageProvider>
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </CustomerLanguageProvider>
    </React.StrictMode>
);

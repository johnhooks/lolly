import React from 'react';

import apiFetch from '@wordpress/api-fetch';
import { createRoot } from '@wordpress/element';

import SettingsPage from './components/settings-page';

import '../css/admin.scss';

// Configure apiFetch with nonce
if (window.lolly?.nonce) {
    apiFetch.use(apiFetch.createNonceMiddleware(window.lolly.nonce));
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('lolly-settings');
    if (container) {
        createRoot(container).render(<SettingsPage />);
    }
});

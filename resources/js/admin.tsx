import React from 'react';

import { createRoot } from '@wordpress/element';

import SettingsPage from './components/settings-page';

import '../css/admin.scss';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('lolly-settings');
    if (container) {
        createRoot(container).render(<SettingsPage />);
    }
});

import React from 'react';
import { createRoot } from 'react-dom/client';
import Dashboard from './components/Dashboard';
import ProjectManager from './components/ProjectManager';
import './style.css';

const mountDashboard = (container) => {
  if (!container.dataset.mounted) {
    const root = createRoot(container);
    root.render(<Dashboard />);
    container.dataset.mounted = 'true';
  }
};

const mountProjectManager = (container) => {
  if (!container.dataset.mounted) {
    const root = createRoot(container);
    root.render(<ProjectManager />);
    container.dataset.mounted = 'true';
  }
};

const initWidgets = () => {
    document.querySelectorAll('.beer-hub-root').forEach(mountDashboard);
    document.querySelectorAll('.project-manager-root').forEach(mountProjectManager);
};

// Initialize on load for frontend
document.addEventListener('DOMContentLoaded', initWidgets);

// Elementor Editor Hooks
window.addEventListener('elementor/frontend/init', () => {
    elementorFrontend.hooks.addAction('frontend/element_ready/beer_supa_dashboard.default', ($scope) => {
        const container = $scope[0].querySelector('.beer-hub-root');
        if (container) mountDashboard(container);
    });

    elementorFrontend.hooks.addAction('frontend/element_ready/beer_supa_project_manager.default', ($scope) => {
        const container = $scope[0].querySelector('.project-manager-root');
        if (container) mountProjectManager(container);
    });
});

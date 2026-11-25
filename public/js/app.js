(() => {
    const appShell = document.getElementById('appShell');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    // The brand/logo is the desktop collapse control. The topbar control is
    // retained only for opening the off-canvas navigation on smaller screens.
    const brandToggle = document.getElementById('sidebarBrandToggle');
    const mobileToggleButton = document.getElementById('sidebarToggle');
    const toggleIcon = document.getElementById('sidebarToggleIcon');
    const closeButton = document.getElementById('sidebarClose');
    const desktopMedia = window.matchMedia('(min-width: 992px)');
    const sidebarStorageKey = 'it-inventory.sidebar-collapsed';

    const readCollapsedPreference = () => {
        try {
            return window.localStorage.getItem(sidebarStorageKey) === '1';
        } catch (error) {
            return false;
        }
    };

    const saveCollapsedPreference = (collapsed) => {
        try {
            window.localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
        } catch (error) {
            // The sidebar remains functional when browser storage is unavailable.
        }
    };

    const hideTooltip = () => {
        const tooltip = document.getElementById('sidebarTooltip');
        if (tooltip) tooltip.hidden = true;
    };

    // In icon-rail mode, every permitted module link must remain keyboard- and
    // pointer-accessible even when its expanded-mode group was saved as closed.
    const syncGroupAccessibilityState = () => {
        const iconRailMode = Boolean(
            desktopMedia.matches && appShell?.classList.contains('sidebar-collapsed')
        );

        sidebar?.querySelectorAll('[data-sidebar-group]').forEach((group) => {
            const button = group.querySelector(':scope > .nav-group-toggle');
            const panel = group.querySelector(':scope > .nav-group-panel');
            if (!panel) return;

            const groupExpanded = button?.getAttribute('aria-expanded') === 'true';
            const panelVisible = iconRailMode || groupExpanded;
            panel.setAttribute('aria-hidden', panelVisible ? 'false' : 'true');
            panel.inert = !panelVisible;
        });
    };

    const closeMobileSidebar = () => {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        if (mobileToggleButton && !desktopMedia.matches) {
            mobileToggleButton.setAttribute('aria-expanded', 'false');
            mobileToggleButton.setAttribute('aria-label', 'Open navigation menu');
            mobileToggleButton.title = 'Open navigation menu';
        }
        if (toggleIcon && !desktopMedia.matches) {
            toggleIcon.className = 'bi bi-list';
        }
        hideTooltip();
    };

    const openMobileSidebar = () => {
        sidebar?.classList.add('open');
        backdrop?.classList.add('show');
        document.body.classList.add('sidebar-open');
        mobileToggleButton?.setAttribute('aria-expanded', 'true');
        mobileToggleButton?.setAttribute('aria-label', 'Close navigation menu');
        if (mobileToggleButton) mobileToggleButton.title = 'Close navigation menu';
        if (toggleIcon) toggleIcon.className = 'bi bi-x-lg';
    };

    const applyDesktopSidebarState = (collapsed) => {
        if (!appShell || !desktopMedia.matches) return;

        appShell.classList.toggle('sidebar-collapsed', collapsed);
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        brandToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        brandToggle?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        if (brandToggle) brandToggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        if (toggleIcon) {
            toggleIcon.className = collapsed
                ? 'bi bi-layout-sidebar'
                : 'bi bi-layout-sidebar-inset';
        }
        syncGroupAccessibilityState();
        hideTooltip();
    };

    const syncSidebarForViewport = () => {
        if (!appShell) return;

        if (desktopMedia.matches) {
            applyDesktopSidebarState(readCollapsedPreference());
        } else {
            appShell.classList.remove('sidebar-collapsed');
            closeMobileSidebar();
            syncGroupAccessibilityState();
        }
    };

    if (appShell && sidebar && brandToggle && mobileToggleButton) {
        syncSidebarForViewport();

        // Clicking anywhere on the brand/logo toggles the desktop sidebar.
        // On mobile it closes the drawer; the topbar menu button opens it.
        brandToggle.addEventListener('click', () => {
            if (desktopMedia.matches) {
                const collapsed = !appShell.classList.contains('sidebar-collapsed');
                saveCollapsedPreference(collapsed);
                applyDesktopSidebarState(collapsed);
            } else {
                closeMobileSidebar();
            }
        });

        mobileToggleButton.addEventListener('click', () => {
            if (desktopMedia.matches) return;
            if (sidebar.classList.contains('open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });

        closeButton?.addEventListener('click', closeMobileSidebar);
        backdrop?.addEventListener('click', closeMobileSidebar);

        if (typeof desktopMedia.addEventListener === 'function') {
            desktopMedia.addEventListener('change', syncSidebarForViewport);
        } else if (typeof desktopMedia.addListener === 'function') {
            desktopMedia.addListener(syncSidebarForViewport);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !desktopMedia.matches) {
                closeMobileSidebar();
            }
        });

        // Each sidebar module group can be expanded or collapsed independently.
        const groupStoragePrefix = 'it-inventory.sidebar-group.';
        const saveGroupPreference = (key, expanded) => {
            try {
                window.localStorage.setItem(`${groupStoragePrefix}${key}`, expanded ? '1' : '0');
            } catch (error) {
                // Group toggles still work for the current page when storage is unavailable.
            }
        };
        const setGroupExpanded = (group, expanded) => {
            const button = group.querySelector(':scope > .nav-group-toggle');
            const panel = group.querySelector(':scope > .nav-group-panel');
            if (!button || !panel) return;

            group.classList.toggle('is-collapsed', !expanded);
            group.classList.toggle('has-active', Boolean(panel.querySelector('.nav-link.active')));
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            panel.setAttribute('aria-hidden', expanded ? 'false' : 'true');
            panel.inert = !expanded;
        };

        const sidebarGroups = Array.from(sidebar.querySelectorAll('[data-sidebar-group]'));
        // The group containing the active route is the only section open on page load.
        // This intentionally takes precedence over stale per-group preferences so the
        // sidebar always reflects the module the user is currently viewing.
        const activeGroup = sidebarGroups.find((group) => group.querySelector('.nav-link.active')) || null;

        sidebarGroups.forEach((group) => {
            const key = group.dataset.sidebarGroup;
            const button = group.querySelector(':scope > .nav-group-toggle');
            if (!key || !button) return;

            setGroupExpanded(group, group === activeGroup);

            button.addEventListener('click', () => {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;

                // Accordion behavior: opening one section closes every other section.
                if (nextExpanded) {
                    sidebarGroups.forEach((otherGroup) => {
                        if (otherGroup === group) return;
                        const otherKey = otherGroup.dataset.sidebarGroup;
                        setGroupExpanded(otherGroup, false);
                        if (otherKey) saveGroupPreference(otherKey, false);
                    });
                }

                setGroupExpanded(group, nextExpanded);
                saveGroupPreference(key, nextExpanded);
                syncGroupAccessibilityState();
            });
        });
        // Restore group-level accessibility after saved states have been applied.
        syncGroupAccessibilityState();

        // Keep the sidebar at the same vertical position after a module page reloads.
        const sidebarScroll = sidebar.querySelector('.sidebar-scroll');
        const sidebarScrollStorageKey = 'it-inventory.sidebar-scroll-top';
        const saveSidebarScrollPosition = () => {
            if (!sidebarScroll) return;
            try {
                window.sessionStorage.setItem(sidebarScrollStorageKey, String(sidebarScroll.scrollTop));
            } catch (error) {
                // Navigation remains functional when session storage is unavailable.
            }
        };
        const restoreSidebarScrollPosition = () => {
            if (!sidebarScroll) return;
            try {
                const savedScrollTop = Number.parseInt(
                    window.sessionStorage.getItem(sidebarScrollStorageKey) || '0',
                    10
                );
                if (Number.isFinite(savedScrollTop) && savedScrollTop >= 0) {
                    sidebarScroll.scrollTop = Math.min(
                        savedScrollTop,
                        Math.max(0, sidebarScroll.scrollHeight - sidebarScroll.clientHeight)
                    );
                }
            } catch (error) {
                // Use the browser's default scroll position when session storage is unavailable.
            }
        };
        restoreSidebarScrollPosition();
        window.addEventListener('pagehide', saveSidebarScrollPosition);

        // Initial states are now applied without animation; later user toggles remain smooth.
        appShell.classList.remove('sidebar-state-initializing');

        // In icon-only mode, show floating labels outside the scrollable sidebar.
        const tooltip = document.createElement('div');
        tooltip.id = 'sidebarTooltip';
        tooltip.className = 'sidebar-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;
        document.body.appendChild(tooltip);

        const showSidebarTooltip = (target) => {
            if (!desktopMedia.matches || !appShell.classList.contains('sidebar-collapsed')) {
                hideTooltip();
                return;
            }

            const label = target.dataset.sidebarTooltip;
            if (!label) return;

            const bounds = target.getBoundingClientRect();
            tooltip.textContent = label;
            tooltip.style.left = `${Math.round(bounds.right + 10)}px`;
            tooltip.style.top = `${Math.round(bounds.top + bounds.height / 2)}px`;
            tooltip.hidden = false;
        };

        sidebar.querySelectorAll('.nav-link').forEach((link) => {
            const label = link.querySelector(':scope > span:not(.nav-count)')?.textContent?.trim();
            if (!label) return;

            link.dataset.sidebarTooltip = label;
            link.setAttribute('aria-label', label);
            link.addEventListener('pointerenter', () => showSidebarTooltip(link));
            link.addEventListener('pointerleave', hideTooltip);
            link.addEventListener('focus', () => showSidebarTooltip(link));
            link.addEventListener('blur', hideTooltip);
        });

        sidebar.querySelectorAll('.nav-group-toggle').forEach((button) => {
            button.setAttribute('aria-label', `Toggle ${button.dataset.sidebarTooltip || 'module'} menu`);
            button.addEventListener('pointerenter', () => showSidebarTooltip(button));
            button.addEventListener('pointerleave', hideTooltip);
            button.addEventListener('focus', () => showSidebarTooltip(button));
            button.addEventListener('blur', hideTooltip);
        });

        // Keep the floating tooltip anchored correctly and prevent stale tooltips
        // from remaining visible after the sidebar or viewport moves.
        sidebar.querySelector('.sidebar-scroll')?.addEventListener('scroll', hideTooltip, { passive: true });
        window.addEventListener('resize', hideTooltip, { passive: true });

        // Close the off-canvas drawer immediately after a navigation choice on
        // touch layouts. Desktop navigation is unaffected.
        sidebar.querySelectorAll('a.nav-link').forEach((link) => {
            link.addEventListener('click', (event) => {
                const opensNewContext = event.defaultPrevented
                    || event.button !== 0
                    || event.metaKey
                    || event.ctrlKey
                    || event.shiftKey
                    || event.altKey
                    || link.target === '_blank';

                if (!opensNewContext) saveSidebarScrollPosition();
                if (!desktopMedia.matches) closeMobileSidebar();
            });
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const prompt = form.getAttribute('data-confirm') || 'Continue with this action?';
            if (!window.confirm(prompt)) event.preventDefault();
        });
    });
})();

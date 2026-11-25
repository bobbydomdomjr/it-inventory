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
            const panelVisible = iconRailMode
                ? group.classList.contains('is-flyout-open')
                : groupExpanded;
            panel.setAttribute('aria-hidden', panelVisible ? 'false' : 'true');
            panel.inert = !panelVisible;
        });
    };

    // A collapsed desktop rail uses floating section menus instead of opening
    // module links inside the sidebar. The panel is temporarily portaled to
    // <body> so the sidebar's scroll container can never clip the flyout.
    let closeSidebarFlyout = () => {};

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

    const syncGroupToggleExpandedAttributes = (iconRailMode) => {
        sidebar?.querySelectorAll('[data-sidebar-group]').forEach((group) => {
            const button = group.querySelector(':scope > .nav-group-toggle');
            const expanded = !iconRailMode && !group.classList.contains('is-collapsed');
            button?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    const applyDesktopSidebarState = (collapsed) => {
        if (!appShell || !desktopMedia.matches) return;

        closeSidebarFlyout();
        appShell.classList.toggle('sidebar-collapsed', collapsed);
        syncGroupToggleExpandedAttributes(collapsed);
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
            closeSidebarFlyout();
            appShell.classList.remove('sidebar-collapsed');
            syncGroupToggleExpandedAttributes(false);
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

        let activeFlyoutGroup = null;
        let activeFlyoutPanel = null;
        let flyoutPinned = false;
        let flyoutOpenTimer = null;
        let flyoutCloseTimer = null;

        const isIconRailMode = () => Boolean(
            desktopMedia.matches && appShell.classList.contains('sidebar-collapsed')
        );

        const clearFlyoutTimers = () => {
            if (flyoutOpenTimer) window.clearTimeout(flyoutOpenTimer);
            if (flyoutCloseTimer) window.clearTimeout(flyoutCloseTimer);
            flyoutOpenTimer = null;
            flyoutCloseTimer = null;
        };

        closeSidebarFlyout = () => {
            clearFlyoutTimers();
            if (!activeFlyoutGroup || !activeFlyoutPanel) return;

            const group = activeFlyoutGroup;
            const panel = activeFlyoutPanel;
            const button = group.querySelector(':scope > .nav-group-toggle');

            group.classList.remove('is-flyout-open');
            panel.classList.remove('sidebar-module-flyout');
            panel.setAttribute('aria-hidden', 'true');
            panel.inert = true;
            panel.style.removeProperty('left');
            panel.style.removeProperty('top');
            group.appendChild(panel);

            activeFlyoutGroup = null;
            activeFlyoutPanel = null;
            flyoutPinned = false;
            button?.setAttribute('aria-expanded', 'false');
            syncGroupAccessibilityState();
        };

        const positionSidebarFlyout = () => {
            if (!activeFlyoutGroup || !activeFlyoutPanel || !isIconRailMode()) return;

            const button = activeFlyoutGroup.querySelector(':scope > .nav-group-toggle');
            if (!button) return;

            const bounds = button.getBoundingClientRect();
            const panel = activeFlyoutPanel;
            const maxTop = Math.max(12, window.innerHeight - panel.offsetHeight - 12);
            const top = Math.min(Math.max(12, bounds.top - 6), maxTop);

            panel.style.left = `${Math.round(bounds.right + 10)}px`;
            panel.style.top = `${Math.round(top)}px`;
        };

        const openSidebarFlyout = (group, pin = false) => {
            if (!isIconRailMode()) return;

            const button = group.querySelector(':scope > .nav-group-toggle');
            const panel = group.querySelector(':scope > .nav-group-panel');
            if (!button || !panel) return;

            clearFlyoutTimers();

            if (activeFlyoutGroup && activeFlyoutGroup !== group) {
                closeSidebarFlyout();
            }

            if (activeFlyoutGroup === group) {
                if (pin) flyoutPinned = true;
                positionSidebarFlyout();
                return;
            }

            activeFlyoutGroup = group;
            activeFlyoutPanel = panel;
            flyoutPinned = pin;
            group.classList.add('is-flyout-open');
            button.setAttribute('aria-expanded', 'true');
            panel.classList.add('sidebar-module-flyout');
            panel.setAttribute('aria-hidden', 'false');
            panel.inert = false;
            document.body.appendChild(panel);
            hideTooltip();
            positionSidebarFlyout();
            syncGroupAccessibilityState();
        };

        const scheduleFlyoutClose = () => {
            if (flyoutPinned || !activeFlyoutGroup) return;
            if (flyoutCloseTimer) window.clearTimeout(flyoutCloseTimer);

            flyoutCloseTimer = window.setTimeout(() => {
                const button = activeFlyoutGroup?.querySelector(':scope > .nav-group-toggle');
                const pointerStillInside = activeFlyoutPanel?.matches(':hover')
                    || button?.matches(':hover');
                const focusStillInside = activeFlyoutPanel?.contains(document.activeElement)
                    || document.activeElement === button;

                if (!pointerStillInside && !focusStillInside) closeSidebarFlyout();
            }, 190);
        };

        // Hover previews the section; clicking pins it open until dismissed.
        sidebarGroups.forEach((group) => {
            const button = group.querySelector(':scope > .nav-group-toggle');
            const panel = group.querySelector(':scope > .nav-group-panel');
            if (!button || !panel) return;

            button.addEventListener('pointerenter', () => {
                if (!isIconRailMode() || flyoutPinned) return;
                if (flyoutOpenTimer) window.clearTimeout(flyoutOpenTimer);
                flyoutOpenTimer = window.setTimeout(() => openSidebarFlyout(group), 75);
            });
            button.addEventListener('pointerleave', () => {
                if (flyoutOpenTimer) window.clearTimeout(flyoutOpenTimer);
                flyoutOpenTimer = null;
                scheduleFlyoutClose();
            });
            button.addEventListener('focus', () => {
                if (isIconRailMode()) openSidebarFlyout(group);
            });
            button.addEventListener('blur', scheduleFlyoutClose);

            button.addEventListener('click', (event) => {
                if (!isIconRailMode()) return;
                event.preventDefault();
                clearFlyoutTimers();

                if (activeFlyoutGroup === group && flyoutPinned) {
                    closeSidebarFlyout();
                    return;
                }

                openSidebarFlyout(group, true);

                // A keyboard-triggered activation should move directly into
                // the floating menu, rather than leaving focus on an icon.
                if (event.detail === 0) {
                    activeFlyoutPanel?.querySelector('a.nav-link')?.focus();
                }
            });

            panel.addEventListener('pointerenter', clearFlyoutTimers);
            panel.addEventListener('pointerleave', scheduleFlyoutClose);
            panel.addEventListener('focusin', clearFlyoutTimers);
            panel.addEventListener('focusout', scheduleFlyoutClose);
            panel.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeSidebarFlyout();
                    button.focus();
                }
            });
        });

        document.addEventListener('pointerdown', (event) => {
            if (!activeFlyoutGroup) return;
            if (activeFlyoutGroup.contains(event.target) || activeFlyoutPanel?.contains(event.target)) return;
            closeSidebarFlyout();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && activeFlyoutGroup) {
                const button = activeFlyoutGroup.querySelector(':scope > .nav-group-toggle');
                closeSidebarFlyout();
                button?.focus();
            }
        });

        window.addEventListener('resize', () => {
            if (activeFlyoutGroup) positionSidebarFlyout();
        }, { passive: true });

        sidebar.querySelector('.sidebar-scroll')?.addEventListener('scroll', () => {
            hideTooltip();
            if (activeFlyoutGroup) closeSidebarFlyout();
        }, { passive: true });

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
                if (isIconRailMode()) return;
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
        if (isIconRailMode()) {
            sidebarGroups.forEach((group) => {
                group.querySelector(':scope > .nav-group-toggle')?.setAttribute('aria-expanded', 'false');
            });
        }
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
            // Group links are shown with their labels inside the flyout and do
            // not need the compact icon-rail tooltip treatment.
            if (link.closest('.nav-group-panel')) return;
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
        });

        // Keep the floating tooltip anchored correctly and prevent stale tooltips
        // from remaining visible after the sidebar or viewport moves.
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

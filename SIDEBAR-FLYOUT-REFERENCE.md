# Enterprise Sidebar Navigation Reference

## Collapsed desktop behavior

- Keep a narrow icon rail so the page canvas retains more working space.
- Hover over a section icon to preview that section's permitted modules in a floating flyout to the right of the rail.
- Click a section icon to pin its flyout open. Click the same icon again, click outside, or press `Esc` to dismiss it.
- Module links remain fully labeled and clickable inside the flyout. The flyout has its own vertical scroll area, so a long module list does not extend the sidebar or push content down the page.
- The active module retains a restrained highlighted state.

## Expanded desktop behavior

- Preserve the existing labeled sidebar and accordion-style section navigation.
- Keep the active section expanded when navigating between its modules.
- Preserve the user's expanded/collapsed sidebar preference.

## Tablet and mobile behavior

- Preserve the existing off-canvas drawer. Floating desktop flyouts are disabled on smaller screens.

## Interaction and accessibility

- Flyout previews are available by pointer hover and keyboard focus.
- Keyboard activation opens the flyout; module links receive normal keyboard focus.
- `Esc` dismisses the flyout and returns focus to its section icon.
- Flyouts are rendered outside the sidebar scroll container to avoid clipping, while retaining the same permission-filtered module links and destinations.

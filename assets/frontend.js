document.addEventListener('click', function (event) {
  const tab = event.target.closest('[data-aso-calendar-tab]');
  if (!tab) return;

  const switcher = tab.closest('[data-aso-calendar-switcher]');
  if (!switcher) return;

  const target = tab.getAttribute('data-aso-calendar-tab');

  switcher.querySelectorAll('[data-aso-calendar-tab]').forEach(function (item) {
    const active = item.getAttribute('data-aso-calendar-tab') === target;
    item.classList.toggle('is-active', active);
    item.setAttribute('aria-selected', active ? 'true' : 'false');
  });

  switcher.querySelectorAll('[data-aso-calendar-panel]').forEach(function (panel) {
    const active = panel.getAttribute('data-aso-calendar-panel') === target;
    panel.classList.toggle('is-active', active);
    panel.hidden = !active;
  });
});

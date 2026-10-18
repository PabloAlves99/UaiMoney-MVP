document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => {
    const group = form.querySelector('[data-category-filter]');
    const subgroup = form.querySelector('[data-subcategory-filter]');
    if (!group || !subgroup) return;
    const update = () => {
      [...subgroup.options].forEach(option => {
        const visible = !option.value || !group.value || option.dataset.group === group.value;
        option.hidden = !visible;
        option.disabled = !visible;
      });
      if (subgroup.selectedOptions[0]?.disabled) subgroup.value = '';
      subgroup.dispatchEvent(new Event('searchable:sync'));
    };
    group.addEventListener('change', update);
    update();
  });
  const bars = [...document.querySelectorAll('.share-bar[data-tooltip]')];
  if (!bars.length) return;
  const tip = document.createElement('div');
  tip.id = 'share-chart-tooltip';
  tip.className = 'share-chart-tooltip';
  tip.setAttribute('role', 'tooltip');
  document.body.appendChild(tip);
  let active = null;
  const show = bar => {
    active = bar;
    tip.textContent = bar.dataset.tooltip;
    tip.classList.add('visible');
    bar.setAttribute('aria-describedby', tip.id);
    const rect = bar.getBoundingClientRect(), box = tip.getBoundingClientRect(), gap = 9;
    tip.style.left = `${Math.min(window.innerWidth - box.width - 8, Math.max(8, rect.left + (rect.width - box.width) / 2))}px`;
    tip.style.top = `${rect.top - box.height - gap >= 8 ? rect.top - box.height - gap : Math.min(window.innerHeight - box.height - 8, rect.bottom + gap)}px`;
  };
  const hide = bar => {
    if (active !== bar) return;
    bar.removeAttribute('aria-describedby');
    active = null;
    tip.classList.remove('visible');
  };
  bars.forEach(bar => {
    bar.addEventListener('mouseenter', () => show(bar));
    bar.addEventListener('mouseleave', () => hide(bar));
    bar.addEventListener('focus', () => show(bar));
    bar.addEventListener('blur', () => hide(bar));
    bar.addEventListener('click', event => { event.stopPropagation(); active === bar ? hide(bar) : show(bar); });
  });
  document.addEventListener('click', () => active && hide(active));
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && active) hide(active); });
  window.addEventListener('resize', () => active && show(active));
  window.addEventListener('scroll', () => active && show(active), true);
});

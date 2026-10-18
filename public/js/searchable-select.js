(() => {
  const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
  const widgets = new WeakMap();
  let active = null;
  let sequence = 0;
  const list = document.createElement('div');
  list.className = 'searchable-select-list';
  list.setAttribute('role', 'listbox');
  list.hidden = true;
  document.body.appendChild(list);

  function close() {
    if (active) {
      active.input.setAttribute('aria-expanded', 'false');
      active.input.removeAttribute('aria-activedescendant');
    }
    active = null;
    list.hidden = true;
  }
  function position() {
    if (!active) return;
    const rect = active.input.getBoundingClientRect();
    const below = window.innerHeight - rect.bottom;
    const above = rect.top;
    const height = Math.max(80, Math.min(260, Math.max(below, above) - 12));
    list.style.width = `${rect.width}px`;
    list.style.left = `${rect.left}px`;
    list.style.maxHeight = `${height}px`;
    list.style.top = below < 160 && above > below ? `${Math.max(4, rect.top - Math.min(height, list.scrollHeight))}px` : `${rect.bottom + 2}px`;
  }
  function available(select) {
    return [...select.options].filter(option => !option.hidden && !option.disabled && !option.closest('optgroup[disabled], optgroup[hidden]'));
  }
  function sync(widget) {
    const { select, input, toggle } = widget;
    input.disabled = select.disabled;
    toggle.disabled = select.disabled;
    input.required = select.required;
    input.setAttribute('aria-required', String(select.required));
    const selected = select.selectedOptions[0];
    input.value = selected && (!select.required || selected.value) ? selected.textContent.trim() : '';
    input.placeholder = selected && !selected.value ? selected.textContent.trim() : 'Selecione ou digite';
    input.setCustomValidity('');
    widget.editing = false;
    if (active === widget && select.disabled) close();
  }
  function choose(widget, option) {
    const { select } = widget;
    select.value = option.value;
    sync(widget);
    close();
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
    sync(widget);
  }
  function render(widget, query = '') {
    if (active && active !== widget) close();
    active = widget;
    const options = available(widget.select).filter(option => normalize(option.textContent).includes(normalize(query)));
    list.replaceChildren();
    list.id = `searchable-options-${widget.id}`;
    widget.input.setAttribute('aria-controls', list.id);
    widget.input.setAttribute('aria-expanded', 'true');
    widget.highlight = -1;
    for (const [index, option] of options.entries()) {
      const item = document.createElement('div');
      item.id = `searchable-option-${widget.id}-${index}`;
      item.className = 'searchable-select-option';
      item.setAttribute('role', 'option');
      item.setAttribute('aria-selected', String(option.selected));
      item.textContent = option.textContent.trim();
      item.addEventListener('pointerdown', event => { event.preventDefault(); choose(widget, option); widget.input.focus(); });
      list.appendChild(item);
    }
    if (!options.length) {
      const empty = document.createElement('div');
      empty.className = 'searchable-select-empty';
      empty.textContent = 'Nenhuma opção encontrada';
      list.appendChild(empty);
    }
    widget.visibleOptions = options;
    list.hidden = false;
    position();
  }
  function highlight(widget, index) {
    const items = list.querySelectorAll('[role="option"]');
    if (!items.length) return;
    widget.highlight = (index + items.length) % items.length;
    items.forEach((item, i) => item.classList.toggle('is-active', i === widget.highlight));
    const item = items[widget.highlight];
    widget.input.setAttribute('aria-activedescendant', item.id);
    item.scrollIntoView({ block: 'nearest' });
  }
  function finishEditing(widget) {
    if (!widget.editing) return;
    const query = normalize(widget.input.value);
    const matches = available(widget.select).filter(option => normalize(option.textContent) === query);
    if (matches.length === 1) choose(widget, matches[0]);
    else if (!query && !widget.select.required) {
      const empty = available(widget.select).find(option => option.value === '');
      if (empty) choose(widget, empty);
      else {
        widget.select.selectedIndex = -1;
        sync(widget);
        widget.select.dispatchEvent(new Event('change', { bubbles: true }));
      }
    } else widget.input.setCustomValidity('Selecione uma opção da lista.');
  }
  function enhance(select) {
    if (widgets.has(select) || select.multiple || select.size > 1) return;
    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select';
    for (const name of ['mb-1', 'mb-2', 'mb-3', 'mt-1', 'mt-2', 'mt-3']) {
      if (select.classList.contains(name)) wrapper.classList.add(name);
    }
    if (select.classList.contains('d-none')) wrapper.classList.add('d-none');
    select.before(wrapper);
    wrapper.appendChild(select);
    select.classList.add('searchable-select-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control searchable-select-input' + (select.classList.contains('form-select-sm') ? ' form-control-sm' : '');
    input.autocomplete = 'off';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    if (select.getAttribute('aria-label')) input.setAttribute('aria-label', select.getAttribute('aria-label'));
    else if (select.labels?.length) {
      const labels = [...select.labels].map(label => { if (!label.id) label.id = `searchable-label-${++sequence}`; return label.id; });
      input.setAttribute('aria-labelledby', labels.join(' '));
    }
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'searchable-select-toggle';
    toggle.tabIndex = -1;
    toggle.setAttribute('aria-label', 'Mostrar opções');
    toggle.innerHTML = '<span aria-hidden="true">▾</span>';
    wrapper.append(input, toggle);
    const widget = { select, input, toggle, id: ++sequence, editing: false, visibleOptions: [], highlight: -1 };
    widgets.set(select, widget);
    sync(widget);
    input.addEventListener('focus', () => { render(widget, widget.editing ? input.value : ''); input.select(); });
    input.addEventListener('input', () => {
      widget.editing = true;
      input.setCustomValidity('Selecione uma opção da lista.');
      render(widget, input.value);
    });
    input.addEventListener('blur', () => { finishEditing(widget); if (active === widget) close(); });
    input.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (active !== widget) render(widget, widget.editing ? input.value : '');
        const next = widget.highlight < 0 && event.key === 'ArrowUp' ? widget.visibleOptions.length - 1 : widget.highlight + (event.key === 'ArrowDown' ? 1 : -1);
        highlight(widget, next);
      } else if (event.key === 'Enter' && active === widget && widget.visibleOptions.length) {
        const exact = widget.visibleOptions.filter(option => normalize(option.textContent) === normalize(input.value));
        const option = widget.highlight >= 0 ? widget.visibleOptions[widget.highlight] : exact.length === 1 ? exact[0] : null;
        if (option) { event.preventDefault(); choose(widget, option); }
      } else if (event.key === 'Escape' && active === widget) { event.preventDefault(); close(); }
    });
    toggle.addEventListener('pointerdown', event => event.preventDefault());
    toggle.addEventListener('click', () => { const wasOpen = active === widget; input.focus(); if (wasOpen) close(); else render(widget, widget.editing ? input.value : ''); });
    select.addEventListener('change', () => sync(widget));
    select.addEventListener('searchable:sync', () => sync(widget));
    select.addEventListener('invalid', event => { event.preventDefault(); input.focus(); input.reportValidity(); });
    for (const label of select.labels || []) label.addEventListener('click', event => { if (event.target === label) { event.preventDefault(); input.focus(); } });
    new MutationObserver(() => {
      if (!widget.editing) sync(widget);
      else { input.disabled = select.disabled; toggle.disabled = select.disabled; input.required = select.required; if (select.disabled && active === widget) close(); }
      if (active === widget) render(widget, widget.editing ? input.value : '');
    }).observe(select, { attributes: true, childList: true, subtree: true, characterData: true });
  }
  function scan(root) {
    if (root.matches?.('select')) enhance(root);
    root.querySelectorAll?.('select').forEach(enhance);
  }
  function start() {
    scan(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => { if (node.nodeType === 1) scan(node); }))).observe(document.body, { childList: true, subtree: true });
  }
  document.addEventListener('pointerdown', event => { if (active && !active.select.parentElement.contains(event.target) && !list.contains(event.target)) close(); });
  window.addEventListener('resize', position);
  window.addEventListener('scroll', position, true);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-import-source]');
  const panel = form?.querySelector('[data-csv-options]');
  if (!panel) return;

  const fileInput = form.querySelector('#import-file');
  const details = panel.querySelector('[data-csv-details]');
  const mode = panel.querySelector('[data-csv-mode]');
  const separator = panel.querySelector('[data-csv-separator]');
  const header = panel.querySelector('[data-csv-header]');
  const sample = panel.querySelector('[data-csv-sample]');
  const columns = Object.fromEntries([...panel.querySelectorAll('[data-csv-column]')].map(select => [select.dataset.csvColumn, select]));
  let content = '';

  function parseRows(text, delimiter, limit = 14) {
    const rows = [];
    let row = [], field = '', quoted = false;
    for (let i = 0; i < text.length && rows.length < limit; i++) {
      const char = text[i];
      if (char === '"') {
        if (quoted && text[i + 1] === '"') { field += '"'; i++; }
        else quoted = !quoted;
      } else if (char === delimiter && !quoted) {
        row.push(field); field = '';
      } else if ((char === '\n' || char === '\r') && !quoted) {
        if (char === '\r' && text[i + 1] === '\n') i++;
        row.push(field);
        if (row.some(value => value.trim() !== '')) rows.push(row);
        row = []; field = '';
      } else field += char;
    }
    if (row.length || field !== '') { row.push(field); rows.push(row); }
    return rows;
  }

  function normalize(value) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }

  function render() {
    if (!content) return;
    const headerIndex = Number(header.value) - 1;
    const candidates = { ponto_virgula: ';', virgula: ',', tabulacao: '\t', barra_vertical: '|' };
    let delimiter = candidates[separator.value];
    if (!delimiter) {
      delimiter = [';', ',', '\t', '|'].sort((a, b) => parseRows(content, b)[headerIndex]?.length - parseRows(content, a)[headerIndex]?.length)[0];
    }
    const rows = parseRows(content, delimiter, headerIndex + 4);
    const titles = rows[headerIndex] || [];
    const matches = {
      data: /^(data|date)(\b|\s|$)/,
      descricao: /^(descricao|historico|description|merchant|estabelecimento)(\b|\s|$)/,
      valor: /^(valor|amount|value)(\b|\s|$)/,
      saida: /^(saida|debito|debit)(\b|\s|$)/,
      entrada: /^(entrada|credito|credit)(\b|\s|$)/,
    };
    for (const [name, select] of Object.entries(columns)) {
      const previous = select.value;
      select.replaceChildren(new Option(name === 'data' || name === 'descricao' ? 'Selecione a coluna' : 'Não usar', ''));
      titles.forEach((title, index) => select.add(new Option(`${index + 1}. ${title.trim() || '(sem título)'}`, String(index))));
      const suggested = titles.findIndex(title => matches[name].test(normalize(title)));
      select.value = previous && [...select.options].some(option => option.value === previous) ? previous : suggested >= 0 ? String(suggested) : '';
      select.dispatchEvent(new Event('searchable:sync'));
    }
    sample.replaceChildren();
    const table = document.createElement('table');
    table.className = 'table table-sm table-bordered mb-0';
    const body = document.createElement('tbody');
    for (const row of rows.slice(headerIndex, headerIndex + 4)) {
      const tr = document.createElement('tr');
      for (const value of row) {
        const cell = document.createElement(row === rows[headerIndex] ? 'th' : 'td');
        cell.textContent = value;
        tr.append(cell);
      }
      body.append(tr);
    }
    table.append(body);
    sample.append(table);
    if (!columns.data.value || !columns.descricao.value || (!columns.valor.value && !columns.saida.value && !columns.entrada.value)) {
      details.open = true;
      mode.value = 'manual';
    }
  }

  fileInput.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    panel.hidden = !file || !file.name.toLowerCase().endsWith('.csv');
    details.open = false;
    mode.value = 'auto';
    content = '';
    if (panel.hidden) return;
    const bytes = await file.arrayBuffer();
    try { content = new TextDecoder('utf-8', { fatal: true }).decode(bytes); }
    catch { content = new TextDecoder('windows-1252').decode(bytes); }
    content = content.replace(/^\uFEFF/, '');
    render();
  });
  const remap = () => {
    mode.value = 'manual';
    Object.values(columns).forEach(select => { select.value = ''; });
    render();
  };
  separator.addEventListener('change', remap);
  header.addEventListener('change', remap);
  details.addEventListener('toggle', () => { if (details.open) mode.value = 'manual'; });
  panel.querySelectorAll('[data-csv-column], [name="csv_inverter_sinal"]').forEach(input => {
    input.addEventListener('change', () => {
      mode.value = 'manual';
      if (input === columns.valor && input.value) {
        columns.saida.value = ''; columns.entrada.value = '';
        columns.saida.dispatchEvent(new Event('searchable:sync'));
        columns.entrada.dispatchEvent(new Event('searchable:sync'));
      }
      if ((input === columns.saida || input === columns.entrada) && input.value) {
        columns.valor.value = '';
        columns.valor.dispatchEvent(new Event('searchable:sync'));
      }
    });
  });
});

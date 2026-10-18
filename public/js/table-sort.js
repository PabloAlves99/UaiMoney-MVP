(() => {
  const collator = new Intl.Collator("pt-BR", { numeric: true, sensitivity: "base" });
  const normalize = (value) => value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  const parseValue = (raw) => {
    const text = String(raw).trim().replace(/\u2212/g, "-");
    if (!text || text === "—") return null;
    let match = text.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (match) return Number(match[3] + match[2] + match[1]);
    match = text.match(/^(\d{2})\/(\d{4})$/);
    if (match) return Number(match[2] + match[1] + "01");
    if (/^\d{4}-\d{2}-\d{2}/.test(text)) return Number(text.slice(0, 10).replaceAll("-", ""));
    if (text === "Meta alcançada") return 0;
    if (/^(?:\d+ anos?(?: e )?)?(?:\d+ m[eê]s(?:es)?)?$/.test(text)) {
      return Number(text.match(/(\d+) anos?/)?.[1] || 0) * 12 + Number(text.match(/(\d+) m[eê]s/)?.[1] || 0);
    }
    const numeric = text.replace(/R\$|%|\s/g, "");
    if (/^[+-]?\d[\d.,]*$/.test(numeric)) {
      const value = numeric.includes(",") ? numeric.replaceAll(".", "").replace(",", ".") : /^[-+]?\d{1,3}(?:\.\d{3})+$/.test(numeric) ? numeric.replaceAll(".", "") : numeric;
      return Number(value);
    }
    return text;
  };
  const compare = (a, b, direction) => {
    if (a === null || b === null) return a === b ? 0 : a === null ? 1 : -1;
    return (typeof a === "number" && typeof b === "number" ? a - b : collator.compare(String(a), String(b))) * direction;
  };
  const cellValue = (cell) => {
    if (!cell) return null;
    if (cell.dataset.sortValue !== undefined) {
      const value = cell.dataset.sortValue;
      return value !== "" && Number.isFinite(Number(value)) ? Number(value) : parseValue(value);
    }
    const check = cell.querySelector('input[type="checkbox"]');
    if (check) return check.checked ? 1 : 0;
    const primary = cell.querySelector(".category-expand strong, .category-child-name strong, .movement-title, strong");
    if (primary) return parseValue(primary.textContent);
    const clone = cell.cloneNode(true);
    clone.querySelectorAll("small, .small, form, .visually-hidden").forEach((node) => node.remove());
    return parseValue(clone.textContent);
  };
  const sortRows = (body, index, direction) => {
    const rows = [...body.rows];
    if (rows.some((row) => [...row.cells].some((cell) => cell.colSpan > 1))) return;
    rows.sort((a, b) => compare(cellValue(a.cells[index]), cellValue(b.cells[index]), direction));
    rows.forEach((row) => body.appendChild(row));
  };
  const sortTable = (table, index, direction) => {
    if (table.classList.contains("category-tree")) {
      const groups = [...table.tBodies].filter((body) => body.querySelector("[data-category-row]")).map((body) => {
        const row = body.rows[0];
        return { body, row, children: document.getElementById(row.querySelector("[data-category-expand]").getAttribute("aria-controls")) };
      });
      groups.sort((a, b) => compare(cellValue(a.row.cells[index]), cellValue(b.row.cells[index]), direction));
      groups.forEach(({ body, children }) => {
        table.appendChild(body);
        if (children) { sortRows(children, index, direction); table.appendChild(children); }
      });
    } else [...table.tBodies].forEach((body) => sortRows(body, index, direction));
  };
  if (typeof module !== "undefined" && module.exports) module.exports = { parseValue, compare, cellValue, sortTable };
  if (typeof document === "undefined") return;
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("table.table").forEach((table) => {
      const headers = [...(table.tHead?.rows[0]?.cells || [])];
      const current = new URL(window.location.href);
      const controls = [];
      let syncMobile = () => {};
      const setSortState = (selected, ascending) => {
        controls.forEach(({ header, button, label }) => {
          const state = header === selected ? (ascending ? "ascending" : "descending") : "none";
          header.setAttribute("aria-sort", state);
          button.setAttribute("aria-label", `${label}: ordenar em ordem ${state === "ascending" ? "decrescente" : "crescente"}`);
        });
        syncMobile();
      };
      let feedback;
      const refreshSortedRows = async (url) => {
        if (!feedback) {
          feedback = document.createElement("p"); feedback.className = "visually-hidden"; feedback.setAttribute("role", "status");
          table.before(feedback);
        }
        feedback.className = "visually-hidden";
        feedback.textContent = "Ordenando tabela";
        table.setAttribute("aria-busy", "true");
        controls.forEach(({ button }) => { button.disabled = true; });
        try {
          const response = await fetch(url, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } });
          if (!response.ok || response.redirected) throw new Error("Não foi possível ordenar a tabela. Tente novamente.");
          const page = new DOMParser().parseFromString(await response.text(), "text/html");
          const updated = page.querySelector("table[data-server-sort]");
          if (!updated || !updated.tBodies.length) throw new Error("Não foi possível ordenar a tabela. Tente novamente.");
          const bodies = [...updated.tBodies].map((body) => document.importNode(body, true));
          [...table.tBodies].forEach((body) => body.remove());
          bodies.forEach((body) => table.appendChild(body));
          const pagination = table.closest(".card-body")?.querySelector(".pagination-links");
          const nextPagination = updated.closest(".card-body")?.querySelector(".pagination-links");
          if (pagination && nextPagination) pagination.replaceWith(document.importNode(nextPagination, true));
          const form = document.querySelector('form[method="get"]');
          if (form) ["ordenacao", "direcao"].forEach((key) => {
            let input = form.querySelector(`input[name="${key}"]`);
            if (!input) { input = document.createElement("input"); input.type = "hidden"; input.name = key; form.appendChild(input); }
            input.value = url.searchParams.get(key);
          });
          feedback.textContent = "Tabela ordenada";
        } finally {
          table.removeAttribute("aria-busy");
          controls.forEach(({ button }) => { button.disabled = false; });
        }
      };
      headers.forEach((header, index) => {
        const label = header.textContent.trim();
        if (!label || normalize(label) === "acoes" || header.dataset.sortable === "false") return;
        header.setAttribute("aria-sort", "none");
        const button = document.createElement("button");
        button.type = "button";
        button.className = "table-sort-button";
        const name = document.createElement("span"); name.textContent = label;
        const indicator = document.createElement("span"); indicator.className = "table-sort-indicator"; indicator.setAttribute("aria-hidden", "true"); indicator.textContent = "↕";
        button.append(name, indicator);
        header.replaceChildren(button);
        controls.push({ header, button, label });
        if (table.hasAttribute("data-server-sort") && current.searchParams.get("ordenacao") === header.dataset.sortKey) header.setAttribute("aria-sort", current.searchParams.get("direcao") === "desc" ? "descending" : "ascending");
        const updateLabel = () => button.setAttribute("aria-label", `${label}: ordenar em ordem ${header.getAttribute("aria-sort") === "ascending" ? "decrescente" : "crescente"}`);
        updateLabel();
        button.addEventListener("click", async () => {
          const ascending = header.getAttribute("aria-sort") !== "ascending";
          if (table.hasAttribute("data-server-sort")) {
            if (!header.dataset.sortKey) return;
            if (table.getAttribute("aria-busy") === "true") return;
            const requested = new URL(current);
            requested.searchParams.set("ordenacao", header.dataset.sortKey);
            requested.searchParams.set("direcao", ascending ? "asc" : "desc");
            requested.searchParams.set("pagina", "1");
            try {
              await refreshSortedRows(requested);
              current.search = requested.search;
              setSortState(header, ascending);
            } catch (error) {
              feedback.className = "small text-danger my-2";
              feedback.textContent = error.message || "Não foi possível ordenar a tabela. Tente novamente.";
            }
            return;
          }
          sortTable(table, index, ascending ? 1 : -1);
          setSortState(header, ascending);
        });
      });
      if (table.classList.contains("category-tree") && controls.length) {
        const toolbar = document.createElement("div"); toolbar.className = "table-sort-mobile";
        const label = document.createElement("label"); label.textContent = "Ordenar por";
        const select = document.createElement("select"); select.className = "form-select form-select-sm";
        controls.forEach((control, index) => { const option = document.createElement("option"); option.value = index; option.textContent = control.label; select.appendChild(option); });
        label.appendChild(select);
        const direction = document.createElement("button"); direction.type = "button"; direction.className = "btn btn-outline-secondary btn-sm"; direction.textContent = "Crescente ↑";
        toolbar.append(label, direction);
        table.parentElement.before(toolbar);
        syncMobile = () => {
          const active = controls.findIndex((control) => control.header.getAttribute("aria-sort") !== "none");
          if (active >= 0) select.value = String(active);
          select.dispatchEvent(new Event('searchable:sync'));
          direction.textContent = controls[Number(select.value)].header.getAttribute("aria-sort") === "descending" ? "Decrescente ↓" : "Crescente ↑";
        };
        select.addEventListener("change", () => { const control=controls[Number(select.value)]; control.header.setAttribute("aria-sort", "none"); control.button.click(); });
        direction.addEventListener("click", () => controls[Number(select.value)].button.click());
        const mobile = window.matchMedia("(max-width: 767.98px)");
        const updateTabOrder = () => controls.forEach((control) => { control.button.tabIndex = mobile.matches ? -1 : 0; });
        mobile.addEventListener("change", updateTabOrder); updateTabOrder();
      }
    });
  });
})();

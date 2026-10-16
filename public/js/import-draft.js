document.addEventListener("DOMContentLoaded", () => {
  const source = document.querySelector("[data-import-source]");
  if (source) {
    const account = source.querySelector("[name='conta_id']");
    const card = source.querySelector("[name='cartao_id']");
    const type = source.querySelector("[name='tipo_padrao']");
    const updateRequired = () => {
      account.required = type.value !== "cartao";
      card.required = type.value === "cartao";
    };
    type.addEventListener("change", updateRequired);
    updateRequired();
  }
  const form = document.querySelector("[data-import-draft]");
  if (!form) return;

  const status = form.querySelector("[data-draft-status]");
  const saveUrl = form.dataset.draftSaveUrl;
  const checkboxes = [...form.querySelectorAll("[data-import-select]")];
  const updateDestination = (row) => {
    const card = row.querySelector("[data-import-target]").value === "cartao";
    const invalidIncome = card && row.dataset.importIncome === "1";
    const checkbox = row.querySelector("[data-import-select]");
    checkbox.disabled = invalidIncome;
    if (invalidIncome) checkbox.checked = false;
    row.querySelector("[data-import-account]").classList.toggle("d-none", card);
    row.querySelector("[data-import-card]").classList.toggle("d-none", !card);
    row.querySelector("[data-import-method]").classList.toggle("d-none", card);
    row.querySelector("[data-import-income-note]")?.classList.toggle("d-none", !invalidIncome);
  };
  form.querySelectorAll("[data-import-row]").forEach(updateDestination);
  let pending = Promise.resolve();
  let revision = 0;

  const values = () => {
    const data = new FormData(form);
    data.set("_auto", "1");
    return data;
  };

  const save = () => {
    const data = values();
    const current = ++revision;
    status.textContent = "Salvando prévia…";
    pending = pending.catch(() => {}).then(async () => {
      const response = await fetch(saveUrl, {
        method: "POST",
        body: data,
        credentials: "same-origin",
        keepalive: true,
      });
      if (!response.ok) throw new Error("Não foi possível salvar a prévia.");
      if (current === revision) status.textContent = "Prévia salva";
    }).catch(() => {
      if (current === revision) status.textContent = "Falha ao salvar. Use Salvar prévia antes de sair.";
    });
  };

  form.addEventListener("change", (event) => {
    if (event.target.matches("[data-import-target]")) updateDestination(event.target.closest("[data-import-row]"));
    if (event.target.matches("[data-import-select], select[name^='categoria'], [data-import-target], [data-import-account], [data-import-card], [data-import-method]")) save();
  });
  form.querySelector("[data-select-all]").addEventListener("click", () => {
    checkboxes.forEach((checkbox) => { checkbox.checked = !checkbox.disabled; });
    save();
  });
  form.querySelector("[data-select-none]").addEventListener("click", () => {
    checkboxes.forEach((checkbox) => { checkbox.checked = false; });
    save();
  });
  form.querySelectorAll("button[data-confirm]").forEach((button) => {
    button.addEventListener("click", (event) => {
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  });
  window.addEventListener("pagehide", () => {
    if (status.textContent === "Salvando prévia…" && navigator.sendBeacon)
      navigator.sendBeacon(saveUrl, values());
  });
});

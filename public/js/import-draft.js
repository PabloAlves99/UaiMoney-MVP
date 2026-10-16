document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("[data-import-draft]");
  if (!form) return;

  const status = form.querySelector("[data-draft-status]");
  const saveUrl = form.dataset.draftSaveUrl;
  const checkboxes = [...form.querySelectorAll("[data-import-select]")];
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
    if (event.target.matches("[data-import-select], select[name^='categoria']")) save();
  });
  form.querySelector("[data-select-all]").addEventListener("click", () => {
    checkboxes.forEach((checkbox) => { checkbox.checked = true; });
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

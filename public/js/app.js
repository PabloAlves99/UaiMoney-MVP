document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });
  const fragment = window.location.hash.slice(1);
  const target = document.getElementById(fragment);
  if (target?.classList.contains("modal") && window.bootstrap) {
    bootstrap.Modal.getOrCreateInstance(target).show();
  }
  const status = document.getElementById("new-status");
  const account = document.getElementById("new-account");
  const date = document.getElementById("new-paid");
  const sync = () => {
    if (!status || !account || !date) return;
    account.required = status.value === "efetivada";
    date.required = status.value === "efetivada";
    date.disabled = status.value !== "efetivada";
  };
  status?.addEventListener("change", sync);
  sync();
});

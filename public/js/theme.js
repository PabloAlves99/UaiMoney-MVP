document.addEventListener("DOMContentLoaded", () => {
  const button = document.querySelector("#themeToggle");

  if (!button) {
    return;
  }

  const storageKey = "uaimoney_theme";

  const getCurrentTheme = () => {
    return document.documentElement.getAttribute("data-bs-theme") || "light";
  };

  const updateButton = () => {
    const theme = getCurrentTheme();

    const isDark = theme === "dark";
    const nextTheme = isDark ? "claro" : "escuro";
    const icon = isDark
      ? '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>'
      : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg>';

    button.innerHTML = icon;
    button.setAttribute("aria-label", `Ativar tema ${nextTheme}`);
    button.setAttribute("title", `Ativar tema ${nextTheme}`);
  };

  /*
   * Mantém a preferência deste navegador.
   */

  localStorage.setItem(storageKey, getCurrentTheme());

  updateButton();

  button.addEventListener("click", async () => {
    const html = document.documentElement;

    const currentTheme = getCurrentTheme();

    const newTheme = currentTheme === "dark" ? "light" : "dark";

    /*
     * Troca imediatamente.
     */

    html.setAttribute("data-bs-theme", newTheme);

    localStorage.setItem(storageKey, newTheme);

    updateButton();

    /*
     * Se não estivermos autenticados,
     * termina aqui.
     */

    if (!window.UaiMoney || !window.UaiMoney.themeUrl) {
      return;
    }

    const csrfInput = document.querySelector("#csrfToken");

    if (!csrfInput) {
      return;
    }

    const formData = new FormData();

    formData.append("tema", newTheme);

    formData.append("_token", csrfInput.value);

    try {
      const response = await fetch(window.UaiMoney.themeUrl, {
        method: "POST",
        body: formData,
      });

      if (!response.ok) {
        const message = await response.text();

        throw new Error(message || "Não foi possível salvar o tema.");
      }
    } catch (error) {
      /*
       * Banco não salvou.
       * Volta ao estado anterior.
       */

      html.setAttribute("data-bs-theme", currentTheme);

      localStorage.setItem(storageKey, currentTheme);

      updateButton();

      console.error("Erro ao alterar tema:", error);
    }
  });
});

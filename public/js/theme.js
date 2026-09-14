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

    button.textContent = theme === "dark" ? "☀️ Claro" : "🌙 Escuro";
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

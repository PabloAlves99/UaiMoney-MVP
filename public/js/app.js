document.addEventListener("DOMContentLoaded", () => {
  const navigation = document.querySelector("[data-navigation]");
  const hideNavigationDropdowns = () => {
    navigation?.querySelectorAll('[data-bs-toggle="dropdown"][aria-expanded="true"]').forEach((toggle) => {
      window.bootstrap?.Dropdown.getInstance(toggle)?.hide();
    });
  };
  const compactMenuToggle = document.querySelector("[data-compact-menu-toggle]");
  const setCompactMenu = (open, restoreFocus = false) => {
    navigation?.classList.toggle("is-mobile-open", open);
    compactMenuToggle?.setAttribute("aria-expanded", String(open));
    compactMenuToggle?.setAttribute("aria-label", open ? "Fechar menu" : "Abrir menu");
    if (!open) hideNavigationDropdowns();
    if (!open && restoreFocus) compactMenuToggle?.focus();
  };
  compactMenuToggle?.addEventListener("click", () => {
    setCompactMenu(!navigation.classList.contains("is-mobile-open"));
  });
  document.addEventListener("click", (event) => {
    if (navigation?.classList.contains("is-mobile-open") && !navigation.contains(event.target)) setCompactMenu(false);
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && navigation?.classList.contains("is-mobile-open") && !navigation.querySelector('.dropdown-menu.show')) setCompactMenu(false, true);
  });
  navigation?.addEventListener("focusout", (event) => {
    if (event.relatedTarget && !navigation.contains(event.relatedTarget)) setCompactMenu(false);
  });
  window.matchMedia("(min-width: 992px)").addEventListener("change", () => setCompactMenu(false));
  const showHeader = (visible) => {
    if (!navigation) return;
    navigation.classList.toggle("is-hidden", !visible);
    navigation.inert = !visible;
  };
  if (navigation) {
    const updateHeaderHeight = () => {
      document.documentElement.style.setProperty("--navigation-height", `${navigation.offsetHeight}px`);
    };
    new ResizeObserver(updateHeaderHeight).observe(navigation);
    updateHeaderHeight();
    let previousScroll = Math.max(0, window.scrollY);
    let scrollTravel = 0;
    let scrollDirection = 0;
    let scrollFramePending = false;
    window.addEventListener("scroll", () => {
      if (scrollFramePending) return;
      scrollFramePending = true;
      requestAnimationFrame(() => {
        const currentScroll = Math.max(0, window.scrollY);
        const delta = currentScroll - previousScroll;
        const direction = Math.sign(delta);
        if (direction && direction !== scrollDirection) scrollTravel = 0;
        scrollTravel += Math.abs(delta);
        if (direction) scrollDirection = direction;
        if (currentScroll <= navigation.offsetHeight) {
          showHeader(true);
        } else if (scrollTravel >= 12) {
          if (direction > 0) {
            setCompactMenu(false);
            showHeader(false);
          } else if (direction < 0) {
            showHeader(true);
          }
          scrollTravel = 0;
        }
        previousScroll = currentScroll;
        scrollFramePending = false;
      });
    }, { passive: true });
  }
  document.querySelectorAll("[data-category-row]").forEach((row) => {
    const toggle = row.querySelector("[data-category-expand]");
    const children = document.getElementById(toggle?.getAttribute("aria-controls"));
    if (!toggle || !children) return;
    const expand = () => {
      children.hidden = !children.hidden;
      toggle.setAttribute("aria-expanded", String(!children.hidden));
    };
    toggle.addEventListener("click", expand);
    row.addEventListener("click", (event) => {
      if (!event.target.closest("a, button, input, label, select, textarea, form")) expand();
    });
  });
  const subcategoryModal = document.getElementById("new-subcategory-modal");
  subcategoryModal?.addEventListener("show.bs.modal", (event) => {
    const trigger = event.relatedTarget;
    if (!trigger?.dataset.parentId) return;
    subcategoryModal.querySelector('[name="grupo_id"]').value = trigger.dataset.parentId;
    subcategoryModal.querySelector("[data-subcategory-parent-label]").textContent = trigger.dataset.parentName;
    subcategoryModal.querySelector('[name="nome"]').value = "";
    subcategoryModal.querySelector('[name="descricao"]').value = "";
  });
  document.querySelectorAll("[data-category-reopen]").forEach((modal) => {
    window.bootstrap?.Modal.getOrCreateInstance(modal).show();
  });
  document.querySelectorAll("[data-classification-toggle]").forEach((checkbox) => {
    checkbox.addEventListener("change", () => checkbox.form.requestSubmit());
  });
  document.addEventListener("submit", (event) => {
    const form = event.target.closest("form[data-confirm]");
    if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
  });
  const fragment = window.location.hash.slice(1);
  const target = document.getElementById(fragment);
  if (target?.classList.contains("modal") && window.bootstrap) {
    bootstrap.Modal.getOrCreateInstance(target).show();
  }
  document.querySelectorAll("[data-analysis-date]").forEach((input) => {
    input.addEventListener("change", () => {
      document.querySelector("[data-analysis-period]").value = "personalizado";
    });
  });
  document.querySelectorAll("[data-chart-tip]").forEach((point) => {
    const show = () => {
      const output = point
        .closest("[data-analysis-chart]")
        ?.querySelector(".analysis-chart-readout");
      if (output) output.textContent = point.dataset.chartTip;
    };
    point.addEventListener("pointerenter", show);
    point.addEventListener("focus", show);
  });
  document.querySelectorAll("[data-analysis-chart]").forEach((chart) => {
    const svg = chart.querySelector("svg");
    const count = Number(svg.dataset.chartCount);
    const resize = () => {
      const width = Math.max(280, chart.clientWidth);
      const position = (index) =>
        70 + ((index + 0.5) * (width - 90)) / Math.max(1, count);
      svg.setAttribute("viewBox", `0 0 ${width} 310`);
      svg
        .querySelectorAll(".plot-grid, .plot-zero")
        .forEach((line) => line.setAttribute("x2", width - 15));
      svg.querySelectorAll("[data-chart-index]").forEach((item) => {
        const index = Number(item.dataset.chartIndex);
        item.setAttribute(
          item.tagName === "circle" ? "cx" : "x",
          position(index),
        );
        if (item.classList.contains("plot-month")) {
          const interval = Math.max(
            1,
            Math.ceil(count / Math.max(2, Math.floor((width - 90) / 48))),
          );
          item.style.display = index % interval === 0 ? "" : "none";
        }
      });
      ["income", "expense"].forEach((type) => {
        const points = [...svg.querySelectorAll(`.plot-point.${type}`)].map(
          (point) => `${point.getAttribute("cx")},${point.getAttribute("cy")}`,
        );
        svg
          .querySelector(`.plot-line.${type}`)
          ?.setAttribute("points", points.join(" "));
      });
    };
    resize();
    new ResizeObserver(resize).observe(chart);
  });
  document.querySelectorAll("[data-movement-form]").forEach((form) => {
    const editing = form.dataset.editing === "1";
    const fixedCard = form.dataset.card === "1";
    const type = form.querySelector("[data-movement-type]");
    const typeValue = () => type.value;
    const typeButtons = document.querySelectorAll(`[data-movement-type-button][data-movement-form-id="${form.id}"]`);
    const mode = form.querySelector("[data-movement-mode]");
    const method = form.querySelector("[data-movement-method]");
    const status = form.querySelector("[data-movement-status]");
    const category = form.querySelector("[data-movement-category]");
    const advanced = form.querySelector("[data-movement-advanced]");
    const description = form.querySelector("[data-category-suggestion]");
    const suggestionFeedback = form.querySelector("[data-suggestion-feedback]");
    const suggestions = JSON.parse(form.dataset.categorySuggestions || "[]");
    description?.addEventListener("change", () => {
      const match = suggestions.find((item) => item.descricao.toLocaleLowerCase("pt-BR") === description.value.trim().toLocaleLowerCase("pt-BR") && item.tipo === typeValue());
      if (match && !category.value) {
        category.value = String(match.subgrupo_id);
        if (suggestionFeedback) suggestionFeedback.textContent = "Categoria sugerida com base em um lançamento anterior. Confira antes de salvar.";
      } else if (suggestionFeedback) suggestionFeedback.textContent = "";
    });
    const sync = () => {
      const income = type.value === "receita";
      form.classList.toggle("movement-form--receita", income);
      form.classList.toggle("movement-form--despesa", type.value === "despesa");
      const bannerTitle = form.querySelector("[data-movement-banner-title]");
      const bannerDescription = form.querySelector("[data-movement-banner-description]");
      if (bannerTitle) bannerTitle.textContent = income ? "Cadastrar receita" : "Cadastrar despesa";
      if (bannerDescription) bannerDescription.textContent = income ? "Registre um valor que entrou nas suas contas." : "Registre um valor que saiu das suas contas.";
      const recurring = mode?.value === "recorrente";
      const installment = mode?.value === "parcelado";
      const creditOption = method?.querySelector('[value="credito"]');
      if (creditOption) {
        creditOption.disabled = typeValue() === "receita" || recurring;
        if (creditOption.disabled && method.value === "credito") method.value = "pix";
      }
      const credit = fixedCard || method?.value === "credito";
      if (!editing && advanced && (credit || recurring || installment)) advanced.open = true;
      category.querySelectorAll("option[data-type]").forEach((option) => {
        option.hidden = option.dataset.type !== typeValue();
        option.disabled = option.hidden;
      });
      if (category.selectedOptions[0]?.disabled) category.value = "";
      const sections = { account: !credit, card: credit, installment, recurring, status: editing || (!credit && !recurring && !installment), competence: !credit && !recurring && !installment };
      form.querySelectorAll("[data-movement-section]").forEach((section) => {
        section.hidden = !sections[section.dataset.movementSection];
        section.querySelectorAll("input, select, textarea").forEach((input) => { input.disabled = section.hidden; });
      });
      if (!fixedCard) {
        status.querySelector('[value="efetivada"]').textContent = typeValue() === "receita" ? "Recebido" : "Pago";
        status.querySelector('[value="pendente"]').textContent = typeValue() === "receita" ? "Ainda vou receber" : "Ainda vou pagar";
      }
      const account = form.querySelector('[name="conta_id"]');
      if (account) account.required = !credit && !recurring && !installment && status.value === "efetivada";
      const card = form.querySelector('[name="cartao_id"]');
      if (card) card.required = credit;
      const paid = form.querySelector('[name="data_efetivacao"]');
      if (paid) { paid.required = status.value === "efetivada"; paid.disabled = status.value !== "efetivada"; }
      const dateLabel = form.querySelector("[data-movement-date-label]");
      if (dateLabel) dateLabel.textContent = credit ? "Data do pagamento" : recurring ? "Primeiro vencimento" : installment ? "Vencimento da primeira parcela" : status.value === "pendente" ? "Vencimento" : typeValue() === "receita" ? "Data do recebimento" : "Data do pagamento";
      if (!editing) {
        const help = form.querySelector("[data-movement-help]");
        help.textContent = credit ? "A compra entra na fatura. Sua conta só muda ao registrar o pagamento da fatura." : recurring ? "Cria uma programação de receitas ou despesas pendentes. Os lançamentos gerados podem ser pagos e editados individualmente." : installment ? "O valor total será dividido em parcelas mensais pendentes." : "Os saldos são atualizados ao marcar o lançamento como pago ou recebido.";
        const amountLabel = form.querySelector('label[for="movement-value"]');
        amountLabel.textContent = installment ? "Valor total (R$)" : "Valor (R$)";
      }
    };
    typeButtons.forEach((button) => button.addEventListener("click", () => {
      type.value = button.dataset.value;
      typeButtons.forEach((item) => {
        const selected = item === button;
        item.classList.toggle("is-selected", selected);
        item.setAttribute("aria-pressed", String(selected));
      });
      sync();
    }));
    [type, mode, method, status].forEach((control) => control?.addEventListener("change", sync));
    sync();
  });
});

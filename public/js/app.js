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
    const mode = form.querySelector("[data-movement-mode]");
    const method = form.querySelector("[data-movement-method]");
    const status = form.querySelector("[data-movement-status]");
    const category = form.querySelector("[data-movement-category]");
    const sync = () => {
      const recurring = mode?.value === "recorrente";
      const installment = mode?.value === "parcelado";
      const creditOption = method?.querySelector('[value="credito"]');
      if (creditOption) {
        creditOption.disabled = type.value === "receita" || recurring;
        if (creditOption.disabled && method.value === "credito") method.value = "pix";
      }
      const credit = fixedCard || method?.value === "credito";
      category.querySelectorAll("option[data-type]").forEach((option) => {
        option.hidden = option.dataset.type !== type.value;
        option.disabled = option.hidden;
      });
      if (category.selectedOptions[0]?.disabled) category.value = "";
      const sections = { account: !credit, card: credit, installment, recurring, status: editing || (!credit && !recurring && !installment), competence: !credit && !recurring && !installment };
      form.querySelectorAll("[data-movement-section]").forEach((section) => {
        section.hidden = !sections[section.dataset.movementSection];
        section.querySelectorAll("input, select, textarea").forEach((input) => { input.disabled = section.hidden; });
      });
      if (!fixedCard) {
        status.querySelector('[value="efetivada"]').textContent = type.value === "receita" ? "Já recebi" : "Já paguei";
        status.querySelector('[value="pendente"]').textContent = type.value === "receita" ? "Ainda vou receber" : "Ainda vou pagar";
      }
      const account = form.querySelector('[name="conta_id"]');
      if (account) account.required = !credit && !recurring && !installment && status.value === "efetivada";
      const card = form.querySelector('[name="cartao_id"]');
      if (card) card.required = credit;
      const paid = form.querySelector('[name="data_efetivacao"]');
      if (paid) { paid.required = status.value === "efetivada"; paid.disabled = status.value !== "efetivada"; }
      const dateLabel = form.querySelector("[data-movement-date-label]");
      if (dateLabel) dateLabel.textContent = credit ? "Data da compra" : recurring ? "Primeiro vencimento" : installment ? "Vencimento da primeira parcela" : status.value === "pendente" ? "Vencimento" : type.value === "receita" ? "Data do recebimento" : "Data do pagamento";
      if (!editing) {
        const help = form.querySelector("[data-movement-help]");
        help.textContent = credit ? "A compra entra na fatura. Sua conta só muda ao registrar o pagamento da fatura." : recurring ? "Cria uma programação de receitas ou despesas pendentes. Os lançamentos gerados podem ser pagos e editados individualmente." : installment ? "O valor total será dividido em parcelas mensais pendentes." : "Os saldos são atualizados ao marcar o lançamento como pago ou recebido.";
        const amountLabel = form.querySelector('label[for="movement-value"]');
        amountLabel.textContent = installment ? "Valor total (R$)" : "Valor (R$)";
      }
    };
    [type, mode, method, status].forEach((control) => control?.addEventListener("change", sync));
    sync();
  });
});

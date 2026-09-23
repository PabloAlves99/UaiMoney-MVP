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
});

const modal = document.getElementById("courseModal");
const sidebar = document.getElementById("sidebar");
if (modal) {
  document.querySelectorAll("[data-modal]").forEach((button) =>
    button.addEventListener("click", () => {
      modal.classList.add("open");
      modal.setAttribute("aria-hidden", "false");
      const courseCodeField = modal.querySelector('input[name="course_code"]');
      if (courseCodeField) {
        courseCodeField.focus();
      }
    }),
  );
  document
    .querySelectorAll("[data-close]")
    .forEach((button) =>
      button.addEventListener("click", () => modal.classList.remove("open")),
    );
  modal.addEventListener("click", (event) => {
    if (event.target === modal) modal.classList.remove("open");
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") modal.classList.remove("open");
  });
}
const menuButton = document.getElementById("menuButton");
if (menuButton && sidebar) {
  menuButton.addEventListener("click", () => sidebar.classList.toggle("open"));
}
document
  .querySelectorAll(".sidebar nav a")
  .forEach((link) =>
    link.addEventListener("click", () => sidebar.classList.remove("open")),
  );

// Animate grade progress bars on page load
requestAnimationFrame(() => {
  document.querySelectorAll(".grade-fill").forEach((fill) => {
    const width = fill.getAttribute("data-width") || "0%";
    setTimeout(() => {
      fill.style.width = width;
    }, 120);
  });
});

const modal = document.getElementById("courseModal");
const sidebar = document.getElementById("sidebar");
document.querySelectorAll("[data-modal]").forEach((button) =>
  button.addEventListener("click", () => {
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    modal.querySelector('input[name="course_code"]').focus();
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
document
  .getElementById("menuButton")
  .addEventListener("click", () => sidebar.classList.toggle("open"));
document
  .querySelectorAll(".sidebar nav a")
  .forEach((link) =>
    link.addEventListener("click", () => sidebar.classList.remove("open")),
  );

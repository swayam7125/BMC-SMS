function responsiveTables() {
  const tables = document.querySelectorAll(".table-responsive-stack");

  tables.forEach((table) => {
    const headers = [];
    table.querySelectorAll("thead th").forEach((th) => {
      headers.push(th.textContent.trim());
    });

    table.querySelectorAll("tbody tr").forEach((tr) => {
      tr.querySelectorAll("td").forEach((td, index) => {
        td.setAttribute("data-label", headers[index]);
      });
    });
  });
}

// Run on initial load and after AJAX content loads
document.addEventListener("DOMContentLoaded", responsiveTables);
$(document).on("ajax:page:loaded", responsiveTables);

document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll(".agente-membresia-switch")
    .forEach(function (switchEl) {
      switchEl.addEventListener("change", function () {
        var postId = this.getAttribute("data-id");
        var activa = this.checked ? 1 : 0;

        fetch(ajaxurl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body:
            "action=toggle_membresia_agente&post_id=" +
            postId +
            "&activa=" +
            activa,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Recargar la página para reflejar los cambios
              location.reload();
            }
          });
      });
    });
});

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("search-input");
  const codigoPostalInput = document.getElementById("codigo-postal-input");
  const estadoInput = document.getElementById("estado-input");
  const searchResults = document.getElementById("search-results");
  const titleCardsSection = document.querySelectorAll(".title-cards-section");
  const cardsContainer = document.querySelectorAll(".cards-container");
  const loadingIndicator = document.getElementById("loading-indicator"); // Referencia al indicador de carga

  let abortController;

  function performSearch() {
    const query = searchInput.value.trim();
    const codigoPostal = codigoPostalInput.value.trim();
    const estado = estadoInput.value.trim();

    // Cancelar la solicitud anterior si existe
    if (abortController) {
      abortController.abort();
    }

    // Crear un nuevo controlador de abortos
    abortController = new AbortController();
    const signal = abortController.signal;

    if (query.length < 6 && codigoPostal.length < 1 && estado.length < 1) {
      searchResults.innerHTML = ""; // Limpiar resultados si la consulta es muy corta
      searchResults.style.display = "none"; // Ocultar el contenedor
      loadingIndicator.style.display = "none";
      titleCardsSection.forEach((section) => {
        section.style.display = "block";
      });
      cardsContainer.forEach((container) => {
        container.style.display = "grid"; // Mostrar contenedor de tarjetas
      });
      return;
    }

    // Mostrar el indicador de carga
    loadingIndicator.style.display = "block";
    searchResults.style.display = "none";
    titleCardsSection.forEach((section) => {
      section.style.display = "none"; // Ocultar secciones de tarjetas
    });
    cardsContainer.forEach((container) => {
      container.style.display = "none"; // Ocultar contenedor de tarjetas
    });

    // Construir la URL de la API personalizada
    let apiUrl = `/wp-json/custom/v1/search?post_type=${encodeURIComponent(
      query
    )}`;
    if (codigoPostal) {
      apiUrl += `&codigo_postal=${encodeURIComponent(codigoPostal)}`;
    }
    if (estado) {
      apiUrl += `&estado=${encodeURIComponent(estado)}`;
    }

    // Realizar la solicitud a la API personalizada
    fetch(apiUrl, { signal })
      .then((response) => response.json())
      .then((posts) => {
        searchResults.innerHTML = ""; // Limpiar resultados anteriores

        if (posts.length === 0) {
          searchResults.innerHTML = "<p>No se encontraron resultados.</p>";
          searchResults.style.display = "block"; // Mostrar el contenedor
          loadingIndicator.style.display = "none";
          titleCardsSection.forEach((section) => {
            section.style.display = "none"; // Ocultar secciones de tarjetas
          });
          cardsContainer.forEach((container) => {
            container.style.display = "none"; // Ocultar contenedor de tarjetas
          });
          return;
        }

        // Agrupar los resultados por categoría
        const groupedResults = {
          agentes: [],
          contratistas: [],
          inversionistas: [],
        };

        posts.forEach((post) => {
          if (groupedResults[post.category]) {
            groupedResults[post.category].push(post);
          }
        });

        // Renderizar los resultados por categoría
        for (const category in groupedResults) {
          if (groupedResults[category].length > 0) {
            const categorySection = document.createElement("div");
            categorySection.classList.add("category-section");
            categorySection.innerHTML = `
                <h2 class="category-section__title">${capitalizeFirstLetter(
                  category
                )}</h2>
                <div class="category-results"></div>
              `;

            const categoryResults =
              categorySection.querySelector(".category-results");

            groupedResults[category].forEach((post) => {
              let linkHomeExist = false;
              let serviceExist = false;

              if (
                post.link_casa_1 ||
                post.link_casa_2 ||
                post.link_casa_3 ||
                post.link_casa_4
              ) {
                linkHomeExist = true;
              }

              if (
                post.servicio_1 ||
                post.servicio_2 ||
                post.servicio_3 ||
                post.servicio_4
              ) {
                serviceExist = true;
              }

              const postElement = document.createElement("div");
              postElement.classList.add("search-result");
              postElement.innerHTML = `
                <article class="modern-card ${
                  post.category == "agentes"
                    ? "agent-profile"
                    : post.category == "contratistas"
                    ? "contractor-profile"
                    : "banker-profile"
                }">
                        <section class="basic-info">
                        <div class="${
                          post.is_pro_user
                            ? "profile-picture-container profile-picture-container-pro"
                            : "profile-picture-container"
                        }">
                        <img
                            src="${post.imagen_perfil}"
                            alt="Profile Picture"
                            class="profile-picture"
                        />
                        <div class="profile-picture-label ${
                          post.is_pro_user
                            ? "profile-picture-label profile-picture-label-pro"
                            : "profile-picture-label"
                        }">Pro</div>
                        </div>
                        <h3 class="modern-card-title">${post.title}</h3>
                        <div class="phone-container">
                            <i class="fa-solid fa-phone-volume"></i>
                            <span>${post.mobile}</span>
                        </div>
                        <div class="social-media">
                        ${
                          post.facebook_link
                            ? `<a href="${post.facebook_link}" class="grid-icon">
                                    <i class="fa-brands fa-facebook"></i>
                                </a>`
                            : ""
                        }
                        ${
                          post.instagram_link
                            ? `<a href="${post.instagram_link}" class="grid-icon">
                                    <i class="fa-brands fa-instagram"></i>
                                </a>`
                            : ""
                        }
                        ${
                          post.tiktok_link
                            ? `<a href="${post.tiktok_link}" class="grid-icon">
                                  <i class="fa-brands fa-tiktok"></i>
                              </a>`
                            : ""
                        }
                        </div>
                        </section>
                        <section class="main-info">
                        <div class="main-info-presentation">
                            <h2 class="main-info-title">${
                              post.category == "agentes"
                                ? "Agente Inmobiliario"
                                : post.category == "contratistas"
                                ? "Contratista"
                                : "Inversionista"
                            }</h2>
                            <div class="website-container">
                            <i class="fa-solid fa-globe"></i>
                            ${
                              post.link_sitio_web
                                ? `<a href="#">${post.link_sitio_web}</a>`
                                : "Sin sitio web"
                            }
                            </div>
                        </div>

                        ${
                          post.category == "contratistas"
                            ? `
                            
                            ${
                              serviceExist
                                ? `
                            <div class="services-container">
                            <h4>Mis servicios</h4>
                              <ul class="services-list-container">
                              ${
                                post.servicio_1
                                  ? `<li>${post.servicio_1}</li>`
                                  : ""
                              }
                              ${
                                post.servicio_2
                                  ? `<li>${post.servicio_2}</li>`
                                  : ""
                              }
                              ${
                                post.servicio_3
                                  ? `<li>${post.servicio_3}</li>`
                                  : ""
                              }
                              ${
                                post.servicio_4
                                  ? `<li>${post.servicio_4}</li>`
                                  : ""
                              }
                              </ul>
                            </div>
                            `
                                : ""
                            }
                              `
                            : linkHomeExist
                            ? `<div class="sale-home-container">
                            <h4>Casas en venta</h4>
                            <div class="sale-home-icons-container">
                            ${
                              post.link_casa_1
                                ? `<a href="${post.link_casa_1}">
                                      <i class="fa-solid fa-house"></i>
                                  </a>`
                                : ""
                            }
                            ${
                              post.link_casa_2
                                ? `<a href="${post.link_casa_2}">
                                    <i class="fa-solid fa-house"></i>
                                </a>`
                                : ""
                            }
                            ${
                              post.link_casa_3
                                ? `<a href="${post.link_casa_3}">
                                    <i class="fa-solid fa-house"></i>
                                </a>`
                                : ""
                            }
                            ${
                              post.link_casa_4
                                ? `<a href="${post.link_casa_4}">
                                    <i class="fa-solid fa-house"></i>
                                </a>`
                                : ""
                            }
                            </div>
                        </div>`
                            : ""
                        }                   

                        <div class="main-info-location">
                            <div class="location-container">
                            <p><strong>Ciudad:</strong> ${post.ciudad}</p>
                            <p><strong>Estado:</strong> ${post.estado}</p>
                            </div>
                            <div class="postal-code-container">
                            <strong>Códigos Postales</strong>
                            <p>${post.codigo_postal}</p>
                            </div>
                        </div>
                        </section>
                        <section class="podcast-button-section">
                        ${
                          post.link_podcast
                            ? `<div class="podcast-button-link-container">
                            <a href="${post.link_podcast}" class="podcast-button-link">
                            <div class="podcast-button-main-content">
                                <i class="fa-solid fa-podcast"></i>
                                <span>Podcast #${post.numero_podcast}</span>
                            </div>
                            <p class="podcast-button-subtitle">youtube.com</p>
                            </a>
                        </div>`
                            : ""
                        }
                        
                        </section>
                    </article>
                `;

              categoryResults.appendChild(postElement);
            });

            searchResults.appendChild(categorySection);
          }
        }

        searchResults.style.display = "block"; // Mostrar el contenedor
        loadingIndicator.style.display = "none"; // Ocultar el indicador de carga
        titleCardsSection.forEach((section) => {
          section.style.display = "none"; // Ocultar secciones de tarjetas
        });
        cardsContainer.forEach((container) => {
          container.style.display = "none"; // Ocultar contenedor de tarjetas
        });
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          console.log("Solicitud cancelada debido a una nueva búsqueda.");
          return;
        }
        console.error("Error al buscar:", error);
        searchResults.innerHTML =
          "<p>Ocurrió un error. Inténtalo de nuevo más tarde.</p>";
        searchResults.style.display = "block"; // Mostrar el contenedor
        loadingIndicator.style.display = "none"; // Ocultar el indicador de carga
        titleCardsSection.forEach((section) => {
          section.style.display = "none"; // Ocultar secciones de tarjetas
        });
        cardsContainer.forEach((container) => {
          container.style.display = "none"; // Ocultar contenedor de tarjetas
        });
      });
  }

  // Función para capitalizar la primera letra de una palabra
  function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  // Escuchar cambios en el input de búsqueda y el selector de categoría
  searchInput.addEventListener("input", performSearch);
  codigoPostalInput.addEventListener("input", performSearch);
  estadoInput.addEventListener("input", performSearch);
});

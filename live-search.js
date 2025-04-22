document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("search-input");
  const codigoPostalInput = document.getElementById("codigo-postal-input");
  const estadoInput = document.getElementById("estado-input");
  const searchResults = document.getElementById("search-results");
  const titleCardsSection = document.querySelectorAll(".title-cards-section");
  const cardsContainer = document.querySelectorAll(".cards-container");
  const loadingIndicator = document.getElementById("loading-indicator"); // Referencia al indicador de carga

  function performSearch() {
    const query = searchInput.value.trim();
    const codigoPostal = codigoPostalInput.value.trim();
    const estado = estadoInput.value.trim();
    console.log(codigoPostal);

    if (query.length < 1) {
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
    let apiUrl = `/wp-json/custom/v1/search?s=${encodeURIComponent(query)}`;
    if (codigoPostal) {
      apiUrl += `&codigo_postal=${encodeURIComponent(codigoPostal)}`;
    }
    if (estado) {
      apiUrl += `&estado=${encodeURIComponent(estado)}`;
    }

    // Realizar la solicitud a la API personalizada
    fetch(apiUrl)
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
                <h2>${capitalizeFirstLetter(category)}</h2>
                <div class="category-results"></div>
              `;

            const categoryResults =
              categorySection.querySelector(".category-results");

            groupedResults[category].forEach((post) => {
              const postElement = document.createElement("div");
              postElement.classList.add("search-result");
              postElement.innerHTML =
                '<section class="banker-container cards-container">;';
              switch (category) {
                case "agentes":
                  postElement.innerHTML = `<article class="parent agent-profile">
                            <div class="grid-profile-image">
                            <img
                                src="${post.imagen_perfil}"
                                alt="${post.title}"  }"
                                class="profile-picture"
                            />
                            </div>
                            <div class="grid-cell grid-title grid-cell-title">
                            Agente de Bienes Raíces
                            </div>
                            <div class="grid-cell grid-name">${post.title}</div>
                            <div class="grid-cell grid-description">
                            ${post.descripcion}
                            </div>
                            <div class="grid-cell grid-mobile grid-cell-title">
                            <i class="fa-solid fa-phone-volume grid-icon"></i>
                            </div>
                            <div class="grid-cell grid-number">
                                ${post.mobile}
                            </div>
                            <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                            <div class="grid-cell grid-languages">
                                ${post.idiomas}
                            </div>
                            <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                            <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                            <div class="grid-cell grid-city-name">
                                ${post.ciudad}
                            </div>
                            <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                            <div class="grid-cell grid-state-name">
                                ${post.estado}
                            </div>
                            <div class="grid-cell grid-social-names">
                            ${
                              post.facebook_link
                                ? `<a href="${post.facebook_link}" class="grid-icon grid-icon-black">
                                                <i class="fa-brands fa-facebook"></i>
                                            </a>`
                                : ""
                            }
                            ${
                              post.instagram_link
                                ? `<a href="${post.instagram_link}" class="grid-icon grid-icon-black">
                                                  <i class="fa-brands fa-instagram"></i>
                                              </a>`
                                : ""
                            }
                            ${
                              post.tiktok_link
                                ? `<a href="${post.tiktok_link}" class="grid-icon grid-icon-black">
                                                    <i class="fa-brands fa-tiktok"></i>
                                                </a>`
                                : ""
                            }
                            </div>
                            <div class="grid-cell grid-codepost-title grid-cell-title">
                            Código Postal
                            </div>
                            <div class="grid-cell grid-codepost-numbers">
                                ${post.codigo_postal}                          
                            </div>
                        </article>`;
                  break;
                case "contratistas":
                  postElement.innerHTML = `<article class="parent contractor-profile">
                            <div class="grid-profile-image">
                            <img
                                src="${post.imagen_perfil}"
                                alt="${post.title}"
                                class="profile-picture"
                            />
                            </div>
                            <div class="grid-cell grid-title grid-cell-title">
                            Contratista
                            </div>
                            <div class="grid-cell grid-name">${post.title}</div>
                            <div class="grid-cell grid-description">
                            ${post.descripcion}
                            </div>
                            <div class="grid-cell grid-mobile grid-cell-title">
                            <i class="fa-solid fa-phone-volume grid-icon"></i>
                            </div>
                            <div class="grid-cell grid-number">
                                ${post.mobile}
                            </div>
                            <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                            <div class="grid-cell grid-languages">
                                ${post.idiomas}
                            </div>
                            <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                            <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                            <div class="grid-cell grid-city-name">
                                ${post.ciudad}
                            </div>
                            <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                            <div class="grid-cell grid-state-name">
                                ${post.estado}
                            </div>
                            <div class="grid-cell grid-social-names">
                            ${
                              post.facebook_link
                                ? `<a href="${post.facebook_link}" class="grid-icon grid-icon-black">
                                                <i class="fa-brands fa-facebook"></i>
                                            </a>`
                                : ""
                            }
                            ${
                              post.instagram_link
                                ? `<a href="${post.instagram_link}" class="grid-icon grid-icon-black">
                                                  <i class="fa-brands fa-instagram"></i>
                                              </a>`
                                : ""
                            }
                            ${
                              post.tiktok_link
                                ? `<a href="${post.tiktok_link}" class="grid-icon grid-icon-black">
                                                    <i class="fa-brands fa-tiktok"></i>
                                                </a>`
                                : ""
                            }
                            </div>
                            <div class="grid-cell grid-codepost-title grid-cell-title">
                            Código Postal
                            </div>
                            <div class="grid-cell grid-codepost-numbers">
                                ${post.codigo_postal}                          
                            </div>
                        </article>`;
                  break;
                case "inversionistas":
                  postElement.innerHTML = `<article class="parent banker-profile">
                            <div class="grid-profile-image">
                            <img
                                src="${post.imagen_perfil}"
                                alt="${post.title}"
                                class="profile-picture"
                            />
                            </div>
                            <div class="grid-cell grid-title grid-cell-title">
                            Inversionista
                            </div>
                            <div class="grid-cell grid-name">${post.title}</div>
                            <div class="grid-cell grid-description">
                            ${post.descripcion}
                            </div>
                            <div class="grid-cell grid-mobile grid-cell-title">
                            <i class="fa-solid fa-phone-volume grid-icon"></i>
                            </div>
                            <div class="grid-cell grid-number">
                                ${post.mobile}
                            </div>
                            <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                            <div class="grid-cell grid-languages">
                                ${post.idiomas}
                            </div>
                            <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                            <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                            <div class="grid-cell grid-city-name">
                                ${post.ciudad}
                            </div>
                            <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                            <div class="grid-cell grid-state-name">
                                ${post.estado}
                            </div>
                            <div class="grid-cell grid-social-names">
                            ${
                              post.facebook_link
                                ? `<a href="${post.facebook_link}" class="grid-icon grid-icon-white">
                                                <i class="fa-brands fa-facebook"></i>
                                            </a>`
                                : ""
                            }
                            ${
                              post.instagram_link
                                ? `<a href="${post.instagram_link}" class="grid-icon grid-icon-white">
                                                  <i class="fa-brands fa-instagram"></i>
                                              </a>`
                                : ""
                            }
                            ${
                              post.tiktok_link
                                ? `<a href="${post.tiktok_link}" class="grid-icon grid-icon-white">
                                                    <i class="fa-brands fa-tiktok"></i>
                                                </a>`
                                : ""
                            }
                            </div>
                            <div class="grid-cell grid-codepost-title grid-cell-title">
                            Código Postal
                            </div>
                            <div class="grid-cell grid-codepost-numbers">
                                ${post.codigo_postal}                          
                            </div>
                        </article>`;
                  break;

                default:
                  postElement.innerHTML = `<p>${post.title}</p>`;
                  break;
              }
              postElement.innerHTML += `</section>`;

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

// Generalized Slider Functionality
const createSlider = function (
  sliderClass,
  btnLeftClass,
  btnRightClass,
  dotContainerClass
) {
  const sliders = document.querySelectorAll(sliderClass);
  const btnLeft = document.querySelector(btnLeftClass);
  const btnRight = document.querySelector(btnRightClass);
  const dotContainer = document.querySelector(dotContainerClass);

  let curSlide = 0;
  const numSlides = sliders.length;

  const createDots = function () {
    sliders.forEach(function (_, i) {
      dotContainer.insertAdjacentHTML(
        "beforeend",
        `<button class="dots__dot" data-slide="${i}"></button>`
      );
    });
  };

  const activateDot = function (slide) {
    dotContainer
      .querySelectorAll(".dots__dot")
      .forEach((dot) => dot.classList.remove("dots__dot--active"));

    dotContainer
      .querySelector(`.dots__dot[data-slide="${slide}"]`)
      .classList.add("dots__dot--active");
  };

  const goToSlide = function (slide) {
    sliders.forEach((slider, i) => {
      slider.style.transform = `translateX(${120 * (i - slide)}%)`;
    });
  };

  const previousSlide = function () {
    if (curSlide === 0) return;
    curSlide--;
    goToSlide(curSlide);
    activateDot(curSlide);
  };

  const nextSlide = function () {
    if (curSlide === numSlides - 1) return;
    curSlide++;
    goToSlide(curSlide);
    activateDot(curSlide);
  };

  // Event Listeners
  btnLeft.addEventListener("click", previousSlide);
  btnRight.addEventListener("click", nextSlide);

  dotContainer.addEventListener("click", function (e) {
    if (e.target.classList.contains("dots__dot")) {
      const { slide } = e.target.dataset;
      goToSlide(slide);
      activateDot(slide);
    }
  });

  // Initialize Slider
  const init = function () {
    createDots();
    goToSlide(0);
    activateDot(0);
  };

  init();
};

// Initialize Sliders
if (window.innerWidth > 530) {
  if (document.querySelector(".slider-agents")) {
    createSlider(
      ".slider-agents",
      ".slider__btn--left",
      ".slider__btn--right",
      ".dots-agents"
    );
  }
  if (document.querySelector(".slider-contractors")) {
    createSlider(
      ".slider-contractors",
      ".slider__btn--left-contractor",
      ".slider__btn--right-contractor",
      ".dots-contractors"
    );
  }
  if (document.querySelector(".slider-investors")) {
    createSlider(
      ".slider-investors",
      ".slider__btn--left-investor",
      ".slider__btn--right-investor",
      ".dots-investors"
    );
  }
}

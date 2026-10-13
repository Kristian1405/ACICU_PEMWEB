/* =========================
FILE: script.js
========================= */

const slider = document.getElementById("slider");
const slides = document.querySelectorAll(".slide");

let current = 0;

function showSlide(index) {
  if (!slides.length) return;

  slides.forEach((slide) => {
    slide.classList.remove("active");
  });

  slides[index].classList.add("active");
}

function nextSlide() {
  if (!slides.length) return;

  current++;

  if (current >= slides.length) {
    current = 0;
  }

  showSlide(current);
}

function prevSlide() {
  if (!slides.length) return;

  current--;

  if (current < 0) {
    current = slides.length - 1;
  }

  showSlide(current);
}

showSlide(current);

/* =========================
MOBILE NAV
========================= */

const hamburger = document.getElementById("hamburger");
const navMenu = document.getElementById("navMenu");
const navLinks = Array.from(document.querySelectorAll(".nav-link"));

const navItems = navLinks
  .map((link) => {
    const target = link.getAttribute("data-target") || link.getAttribute("href")?.slice(1) || "";
    const section = target ? document.getElementById(target) : null;
    return section ? { link, section, target } : null;
  })
  .filter(Boolean);

function setActiveLink(activeId) {
  navLinks.forEach((link) => {
    const target = link.getAttribute("data-target") || link.getAttribute("href")?.slice(1) || "";
    link.classList.toggle("active", target === activeId);
  });
}

function scrollToSection(targetId) {
  const section = document.getElementById(targetId);
  if (!section) return;

  section.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });

  setActiveLink(targetId);
}

function updateActiveLinkFromScroll() {
  if (!navItems.length) return;

  const viewportCenter = window.innerHeight / 2;
  let activeId = navItems[0].target;

  for (const { target, section } of navItems) {
    const rect = section.getBoundingClientRect();

    if (rect.top <= viewportCenter && rect.bottom >= viewportCenter) {
      activeId = target;
      break;
    }

    if (rect.top <= viewportCenter) {
      activeId = target;
    }
  }

  setActiveLink(activeId);
}

if (hamburger && navMenu) {
  hamburger.addEventListener("click", () => {
    navMenu.classList.toggle("active");
  });
}

navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    const target = link.getAttribute("data-target") || link.getAttribute("href")?.slice(1) || "";
    if (!target) return;

    if (navMenu) {
      navMenu.classList.remove("active");
    }

    scrollToSection(target);
  });
});

if (navItems.length) {
  window.addEventListener("scroll", updateActiveLinkFromScroll, { passive: true });
  window.addEventListener("resize", updateActiveLinkFromScroll);
  updateActiveLinkFromScroll();
}

/* =========================
SWIPE SLIDER MOBILE
========================= */

if (slider) {
  let startX = 0;
  let endX = 0;

  slider.addEventListener("touchstart", (e) => {
    startX = e.touches[0].clientX;
  });

  slider.addEventListener("touchend", (e) => {
    endX = e.changedTouches[0].clientX;

    if (startX - endX > 50) {
      nextSlide();
    }

    if (endX - startX > 50) {
      prevSlide();
    }
  });
}
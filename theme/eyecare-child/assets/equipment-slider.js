(function () {
  'use strict';

  var sliders = document.querySelectorAll('[data-equipment-slider]');
  if (!sliders.length) return;

  sliders.forEach(function (slider) {
    slider.classList.add('has-js');
    var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-equipment-slide]'));
    var dots = Array.prototype.slice.call(slider.querySelectorAll('[data-equipment-dot]'));
    var viewport = slider.querySelector('[data-equipment-viewport]');
    var previous = slider.querySelector('[data-equipment-prev]');
    var next = slider.querySelector('[data-equipment-next]');
    var toggle = slider.querySelector('[data-equipment-toggle]');
    var status = slider.querySelector('[data-equipment-status]');
    var index = 0;
    var timer = null;
    var pointerStart = null;
    var pausedByUser = false;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var delay = 6000;

    function setSlide(nextIndex) {
      index = (nextIndex + slides.length) % slides.length;
      slider.style.setProperty('--equipment-offset', (-index * 100) + '%');

      slides.forEach(function (slide, slideIndex) {
        var active = slideIndex === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });

      dots.forEach(function (dot, dotIndex) {
        var active = dotIndex === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      if (status) {
        var title = slides[index].querySelector('h3');
        status.textContent = 'Thiết bị ' + (index + 1) + ' trên ' + slides.length + ': ' + (title ? title.textContent : '');
      }
    }

    function stop() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (reducedMotion.matches || pausedByUser || document.hidden || slides.length < 2) return;
      timer = window.setInterval(function () { setSlide(index + 1); }, delay);
    }

    function move(step) {
      setSlide(index + step);
      start();
    }

    if (previous) previous.addEventListener('click', function () { move(-1); });
    if (next) next.addEventListener('click', function () { move(1); });
    if (toggle) {
      toggle.addEventListener('click', function () {
        pausedByUser = !pausedByUser;
        toggle.setAttribute('aria-pressed', pausedByUser ? 'true' : 'false');
        toggle.setAttribute('aria-label', pausedByUser ? 'Tiếp tục tự động chuyển' : 'Tạm dừng tự động chuyển');
        slider.classList.toggle('is-paused', pausedByUser);
        if (pausedByUser) stop(); else start();
      });
    }
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        setSlide(Number(dot.getAttribute('data-equipment-dot')) || 0);
        start();
      });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', function (event) {
      if (!slider.contains(event.relatedTarget)) start();
    });
    slider.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); move(1); }
      if (event.key === 'Home') { event.preventDefault(); setSlide(0); start(); }
      if (event.key === 'End') { event.preventDefault(); setSlide(slides.length - 1); start(); }
    });

    if (viewport) {
      viewport.addEventListener('pointerdown', function (event) {
        pointerStart = event.clientX;
        stop();
      });
      viewport.addEventListener('pointerup', function (event) {
        if (pointerStart !== null) {
          var distance = event.clientX - pointerStart;
          if (Math.abs(distance) > 42) move(distance > 0 ? -1 : 1);
        }
        pointerStart = null;
        start();
      });
      viewport.addEventListener('pointercancel', function () { pointerStart = null; start(); });
    }

    if (reducedMotion.addEventListener) {
      reducedMotion.addEventListener('change', start);
    } else if (reducedMotion.addListener) {
      reducedMotion.addListener(start);
    }
    document.addEventListener('visibilitychange', start);
    setSlide(0);
    start();
  });
}());

/*global dotclear */
'use strict';

const dotclear_berlin = dotclear.getData('dotclear_berlin');

// Button templates
dotclear_berlin.template = {
  hamburger: `<button id="hamburger" type="button" aria-label="${dotclear_berlin.navigation}" aria-expanded="false"></button>`,
  offcanvas: {
    on: `<button id="offcanvas-on" type="button"><span class="visually-hidden">${dotclear_berlin.show_menu}</span></button>`,
    off: `<button id="offcanvas-off" type="button"><span class="visually-hidden">${dotclear_berlin.hide_menu}</span></button>`,
  },
};

document.querySelector('html').classList.add('js');

{
  // Show/Hide main menu
  const header_nav = document.querySelector('.header__nav');
  const hamburger = new DOMParser().parseFromString(dotclear_berlin.template.hamburger, 'text/html').body.firstElementChild;
  header_nav.insertAdjacentElement('beforebegin', hamburger);
  header_nav.classList.add('hide');

  // Show/Hide sidebar on small screens
  const main = document.getElementById('main');
  const offcanvas = new DOMParser().parseFromString(dotclear_berlin.template.offcanvas.on, 'text/html').body.firstElementChild;
  main.insertBefore(offcanvas, main.firstChild);
}

document.addEventListener('DOMContentLoaded', () => {
  // Show/Hide main menu
  const header_nav = document.querySelector('.header__nav');
  const hamburger = document.getElementById('hamburger');
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    if (hamburger.classList.contains('open')) {
      hamburger.setAttribute('aria-expanded', 'true');
      header_nav.classList.add('show');
      header_nav.classList.remove('hide');
      document.querySelector('.header__nav li.li-first a').focus();
      return;
    }
    hamburger.setAttribute('aria-expanded', 'false');
    header_nav.classList.add('hide');
    header_nav.classList.remove('show');
  });

  // Show/Hide sidebar on small screens
  const offcanvas = document.getElementById('offcanvas-on');
  offcanvas.addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.getElementById('wrapper');
    const footer = document.getElementById('footer');
    const button = new DOMParser().parseFromString(dotclear_berlin.template.offcanvas.off, 'text/html').body.firstElementChild;
    wrapper.classList.add('off-canvas');
    footer.classList.add('off-canvas');
    sidebar.insertBefore(button, sidebar.firstChild);
    button.focus({
      preventScroll: true,
    });
    button.addEventListener('click', (evt) => {
      wrapper.classList.remove('off-canvas');
      footer.classList.remove('off-canvas');
      evt.target.remove();
      offcanvas.focus();
    });
  });

  // totop init
  const gotop_btn = document.getElementById('gotop');
  const gotop_link = document.querySelector('#gotop a');
  gotop_link.setAttribute('title', gotop_link.textContent);
  gotop_link.innerHTML =
    '<svg width="24px" height="24px" viewBox="1 -6 524 524" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M460 321L426 355 262 192 98 355 64 321 262 125 460 321Z"></path></svg>';
  gotop_btn.style.width = '32px';
  gotop_btn.style.height = '32px';
  gotop_btn.style.padding = '3px 0';

  // totop scroll
  window.addEventListener('scroll', () => {
    if (document.querySelector('html').scrollTop === 0) {
      gotop_btn.classList.add('hide');
      gotop_btn.classList.remove('show');
    } else {
      gotop_btn.classList.add('show');
      gotop_btn.classList.remove('hide');
    }
  });
  gotop.addEventListener('click', (e) => {
    const isReduced =
      window.matchMedia(`(prefers-reduced-motion: reduce)`) === true ||
      window.matchMedia(`(prefers-reduced-motion: reduce)`).matches === true;
    if (isReduced) {
      document.querySelector('html').scrollTop = 0;
    } else {
      function scrollTo(element, to, duration) {
        const easeInOutQuad = (time, ease_start, ease_change, ease_duration) => {
          time /= ease_duration / 2;
          if (time < 1) return (ease_change / 2) * time * time + ease_start;
          time--;
          return (-ease_change / 2) * (time * (time - 2) - 1) + ease_start;
        };
        let currentTime = 0;
        const start = element.scrollTop;
        const change = to - start;
        const increment = 20;
        const animateScroll = () => {
          currentTime += increment;
          element.scrollTop = easeInOutQuad(currentTime, start, change, duration);
          if (currentTime < duration) {
            setTimeout(animateScroll, increment);
          }
        };
        animateScroll();
      }
      scrollTo(document.querySelector('html'), 0, 800);
    }
    e.preventDefault();
  });

  // scroll comment preview if present
  document.getElementById('pr')?.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
});

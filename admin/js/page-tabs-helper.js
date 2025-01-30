/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.pageTabs = function (startTab, opts) {
    const defaults = {
      containerClass: 'part-tabs',
      partPrefix: 'part-',
      contentClass: 'multi-part',
      activeClass: 'part-tabs-active',
      idTabPrefix: 'part-tabs-',
    };

    // Helper functions

    /**
     * Creates tabs.
     *
     * @param      {object}  options  The options
     */
    const createTabs = (options) => {
      const contentElements = document.querySelectorAll(`.${options.contentClass}`);
      const lis = [];

      for (const content of contentElements) {
        content.style.display = 'none';
        lis.push(`<li id="${options.idTabPrefix}${content.id}"><a href="#${content.id}">${content.title}</a></li>`);

        content.id = `${options.partPrefix}${content.id}`;
        content.title = '';
      }

      const container = document.createElement('div');
      container.className = options.containerClass;
      container.innerHTML = `<ul>${lis.join('')}</ul>`;

      const firstContent = document.querySelector(`.${options.contentClass}`);
      if (firstContent) {
        firstContent.parentNode.insertBefore(container, firstContent);
      }
    };

    const getHash = (href = '') => href.replace(/.*#/, '');

    /**
     * Activate a tab
     *
     * @param      {string}  tab      The tab
     * @param      {object}  options  The options
     */
    const clickTab = (tab, options) => {
      const tabsContainer = document.querySelector(`.${options.containerClass} ul`);
      const defaultTab = tabsContainer.querySelector('li a')?.getAttribute('href') || '';
      const anchor = !tab ? getHash(defaultTab) : tab;
      const tabElement = tabsContainer.querySelector(`li a[href="#${anchor}"]`)?.parentElement;
      if (tabElement) {
        tabElement.click();
      }
    };

    /**
     * Return the URL hash (without subhash — #hash[.subhash])
     *
     * @return     {string}  The location hash.
     */
    const getLocationHash = () => {
      const hashParts = getHash(window.location.hash).split('.');
      return hashParts[0];
    };

    /**
     * Return the URL subhash if present (without hash — #hash[.subhash])
     *
     * @return     {string}  The location subhash.
     */
    const getLocationSubhash = () => {
      const hashParts = getHash(window.location.hash).split('.');
      return hashParts[1];
    };

    // Cope with given parameters
    const options = { ...defaults, ...opts };
    let activeTab = startTab || '';
    const hash = getLocationHash();
    const subhash = getLocationSubhash();

    if (hash) {
      window.scrollTo(0, 0);
      activeTab = hash;
    } else if (!activeTab) {
      // open first part
      const firstPart = document.querySelector(`.${options.contentClass}`);
      activeTab = firstPart ? firstPart.id : '';
    }

    createTabs(options);

    const tabsContainer = document.querySelector(`.${options.containerClass} ul`);

    if (tabsContainer) {
      tabsContainer.addEventListener('click', (event) => {
        const target = event.target.closest('li');
        if (!target || target.classList.contains(options.activeClass)) return;

        const activeTabElement = tabsContainer.querySelector(`li.${options.activeClass}`);
        if (activeTabElement) {
          activeTabElement.removeAttribute('aria-selected');
          activeTabElement.classList.remove(options.activeClass);
        }

        target.classList.add(options.activeClass);
        target.setAttribute('aria-selected', 'true');

        const activeContent = document.querySelector(`.${options.contentClass}.active`);
        if (activeContent) {
          activeContent.classList.remove('active');
          activeContent.style.display = 'none';
        }

        const partToActivate = document.getElementById(
          `${options.partPrefix}${getHash(target.querySelector('a').getAttribute('href'))}`,
        );

        if (!partToActivate) {
          return;
        }

        partToActivate.classList.add('active');
        partToActivate.style.display = '';

        if (!partToActivate.classList.contains('loaded')) {
          partToActivate.dispatchEvent(new Event('onetabload'));
          partToActivate.classList.add('loaded');
        }

        partToActivate.dispatchEvent(new Event('tabload'));
      });

      window.addEventListener('hashchange', () => {
        clickTab(getLocationHash(), options);
      });

      clickTab(activeTab, options);
    }

    if (subhash) {
      const element = document.getElementById(subhash);
      if (element) {
        // Check if currently hidden, and if so try to display it
        if (!element.checkVisibility()) {
          const findFirstVisibleParent = (element) => {
            let parent = element.parentElement;
            while (parent) {
              if (parent.checkVisibility?.()) return parent;
              parent = parent.parentElement;
            }
            return null;
          };
          const parent = findFirstVisibleParent(element);
          if (parent) {
            const button = parent.querySelector('.details-cmd');
            if (button) button.click();
          }
        }

        // Tab displayed, now scroll to the sub-part if defined in original document.location (#tab.sub-part)
        element.scrollIntoView();
        element.focus();

        // Give focus to the sub-part if possible
        element.classList.add('focus');
        element.addEventListener(
          'focusout',
          () => {
            element.classList.remove('focus');
          },
          { once: true },
        );
      }
    }

    return this;
  };
});

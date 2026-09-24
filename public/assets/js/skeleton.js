/* Global helpers for real async loading states. No timers are used. */
(function (window, document) {
  'use strict';

  function element(id) { return typeof id === 'string' ? document.getElementById(id) : id; }
  function setHidden(id, hidden) { const node = element(id); if (node) node.hidden = hidden; return node; }

  window.showSkeleton = function (id) { return setHidden(id, false); };
  window.hideSkeleton = function (id) { return setHidden(id, true); };
  window.showContent = function (id) { return setHidden(id, false); };
  window.hideContent = function (id) { return setHidden(id, true); };

  window.withSkeleton = async function (skeletonId, contentId, request, onError) {
    showSkeleton(skeletonId); hideContent(contentId);
    try {
      const result = await request();
      hideSkeleton(skeletonId); showContent(contentId);
      return result;
    } catch (error) {
      hideSkeleton(skeletonId);
      if (typeof onError === 'function') onError(error);
      throw error;
    }
  };

  window.whenChartRendered = function (chart, skeletonId, contentId) {
    const complete = function () { hideSkeleton(skeletonId); showContent(contentId); };
    if (chart && typeof chart.render === 'function') {
      return Promise.resolve(chart.render()).then(function () { requestAnimationFrame(complete); });
    }
    requestAnimationFrame(complete);
    return Promise.resolve(chart);
  };

  // Use for HTML fragments returned by an MVC AJAX endpoint.
  window.fetchIntoRegion = async function (url, options) {
    const settings = Object.assign({ method: 'GET' }, options || {});
    const target = element(settings.contentId);
    const empty = element(settings.emptyId);
    showSkeleton(settings.skeletonId); hideContent(settings.contentId);
    if (empty) empty.hidden = true;
    try {
      const response = await fetch(url, settings);
      if (!response.ok) throw new Error('The request could not be completed.');
      const markup = await response.text();
      if (target) target.innerHTML = markup;
      hideSkeleton(settings.skeletonId); showContent(settings.contentId);
      return markup;
    } catch (error) {
      hideSkeleton(settings.skeletonId);
      if (empty) empty.hidden = false;
      throw error;
    }
  };

  function hydrateImages() {
    document.querySelectorAll('[data-skeleton-image]').forEach(function (image) {
      const wrapper = image.closest('.skeleton-image-wrap');
      if (!wrapper || image.complete) { if (wrapper) wrapper.classList.remove('is-loading'); return; }
      wrapper.classList.add('is-loading');
      image.addEventListener('load', function () { wrapper.classList.remove('is-loading'); }, { once: true });
      image.addEventListener('error', function () { wrapper.classList.remove('is-loading'); }, { once: true });
    });
  }
  document.addEventListener('DOMContentLoaded', hydrateImages);
})(window, document);

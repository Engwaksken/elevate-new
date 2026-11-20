/* {{ app(\App\Services\SettingsService::class)->get('branding.system_name', 'ElevateHer360') }} careers widget.
 * Usage: <div data-elevateher-jobs></div>                       all open positions
 *        <div data-elevateher-jobs data-job="job-slug"></div>  one position with its application form
 */
(function () {
  var base = @json($base);
  var frames = [];

  function mount(el) {
    if (el.getAttribute('data-eh-mounted')) return;
    el.setAttribute('data-eh-mounted', '1');
    var job = el.getAttribute('data-job');
    var src = base + (job ? '/' + encodeURIComponent(job) : '') + '?host=' + encodeURIComponent(location.host);
    var frame = document.createElement('iframe');
    frame.src = src;
    frame.title = el.getAttribute('data-title') || 'Job openings';
    frame.loading = 'lazy';
    frame.setAttribute('scrolling', 'no');
    frame.style.cssText = 'width:100%;border:0;min-height:' + (el.getAttribute('data-min-height') || '480') + 'px;display:block;overflow:hidden';
    el.appendChild(frame);
    frames.push(frame);
  }

  window.addEventListener('message', function (e) {
    if (!e.data || typeof e.data.ehCareersHeight !== 'number') return;
    for (var i = 0; i < frames.length; i++) {
      if (frames[i].contentWindow === e.source) {
        frames[i].style.height = Math.max(200, Math.ceil(e.data.ehCareersHeight) + 8) + 'px';
      }
    }
  });

  function init() {
    var nodes = document.querySelectorAll('[data-elevateher-jobs]');
    for (var i = 0; i < nodes.length; i++) mount(nodes[i]);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

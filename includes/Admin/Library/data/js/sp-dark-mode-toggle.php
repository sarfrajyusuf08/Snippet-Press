<?php
/**
 * Dark Mode Toggle (hardened version)
 * - No template literals, no HTML-looking characters in comments/strings
 * - Injects CSS and a floating toggle button; persists choice in localStorage
 */

return [
    'slug'        => 'sp-dark-mode-toggle',
    'title'       => __( 'Dark Mode Toggle (System + Manual)', 'snippet-press' ),
    'description' => __( 'Adds a floating Light/Dark toggle button and remembers user preference.', 'snippet-press' ),
    'category'    => 'frontend',
    'tags'        => [ 'dark mode', 'theme', 'accessibility', 'ui' ],
    'highlights'  => [
        __( 'Robust mount after DOM ready.', 'snippet-press' ),
        __( 'No template literals; safe in aggressive script contexts.', 'snippet-press' ),
        __( 'Uses class sp-dark on the html element.', 'snippet-press' ),
    ],
    'code'        => <<<'JS'
(function(){
  var STORAGE_KEY = 'sp-theme';
  var CLASS_DARK  = 'sp-dark';
  var BTN_ID      = 'sp-dark-toggle';

  function init(){
    if (document.getElementById(BTN_ID)) return;
    if (!document.body) { setTimeout(init, 30); return; }

    // Build CSS as a plain string (no backticks)
    var cssParts = [];
    cssParts.push('html.' + CLASS_DARK + ', html.' + CLASS_DARK + ' body { background:#0f172a !important; color:#e2e8f0 !important; }');
    cssParts.push('html.' + CLASS_DARK + ' a { color:#93c5fd !important; }');
    cssParts.push('html.' + CLASS_DARK + ' pre, html.' + CLASS_DARK + ' code { background:#1e293b !important; color:#f1f5f9 !important; }');
    cssParts.push('html.' + CLASS_DARK + ' input, html.' + CLASS_DARK + ' textarea, html.' + CLASS_DARK + ' select { background:#1e293b !important; color:#f1f5f9 !important; border-color:#475569 !important; }');
    cssParts.push('html.' + CLASS_DARK + ' header, html.' + CLASS_DARK + ' .site-header, html.' + CLASS_DARK + ' footer, html.' + CLASS_DARK + ' .site-footer { background:#0f172a !important; color:#e2e8f0 !important; }');
    cssParts.push('#' + BTN_ID + ' { position:fixed; left:16px; bottom:16px; padding:10px 12px; font-size:18px; border-radius:9999px; border:1px solid #e5e7eb; background:#fff; color:#111827; box-shadow:0 2px 8px rgba(0,0,0,.15); cursor:pointer; z-index:2147483647; }');
    cssParts.push('html.' + CLASS_DARK + ' #' + BTN_ID + ' { background:#334155; color:#f8fafc; border-color:#475569; }');
    cssParts.push('#' + BTN_ID + ':focus { outline:2px solid #3b82f6; outline-offset:2px; }');

    var style = document.createElement('style');
    style.id = 'sp-dark-toggle-style';
    style.type = 'text/css';
    style.appendChild(document.createTextNode(cssParts.join(' ')));
    document.head.appendChild(style);

    var btn = document.createElement('button');
    btn.id = BTN_ID;
    btn.setAttribute('aria-label','Toggle dark mode');
    btn.type = 'button';
    document.body.appendChild(btn);

    function setIcon(isDark){
      btn.textContent = isDark ? '☀️' : '🌙';
    }

    var saved = null;
    try { saved = localStorage.getItem(STORAGE_KEY); } catch(e){}

    var prefersDark = false;
    try { prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; } catch(e){}

    var startDark = (saved === 'dark') || (saved === null && !!prefersDark);
    document.documentElement.classList.toggle(CLASS_DARK, !!startDark);
    setIcon(!!startDark);

    btn.addEventListener('click', function(){
      var nowDark = !document.documentElement.classList.contains(CLASS_DARK);
      document.documentElement.classList.toggle(CLASS_DARK, nowDark);
      setIcon(nowDark);
      try { localStorage.setItem(STORAGE_KEY, nowDark ? 'dark' : 'light'); } catch(e){}
    });

    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      if (mq.addEventListener) {
        mq.addEventListener('change', function(e){
          var hasChoice = null;
          try { hasChoice = localStorage.getItem(STORAGE_KEY); } catch(_){}
          if (hasChoice) return;
          document.documentElement.classList.toggle(CLASS_DARK, !!e.matches);
          setIcon(!!e.matches);
        });
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
JS,

    'type'        => 'js',
    'scopes'      => [ 'frontend' ],
    'priority'    => 10,
    'status'      => 'disabled',
];

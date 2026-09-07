(function () {
  'use strict';

  var shell = document.querySelector('.app-shell');
  var toggle = document.getElementById('sidebar-toggle');
  var backdrop = document.querySelector('.sidebar-backdrop');

  function closeSidebar() {
    if (shell) {
      shell.classList.remove('sidebar-open');
    }
  }

  if (toggle && shell) {
    toggle.addEventListener('click', function () {
      shell.classList.toggle('sidebar-open');
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeSidebar();
    }
  });
  document.querySelectorAll('.sidebar-nav a').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.matchMedia('(max-width: 980px)').matches) {
        closeSidebar();
      }
    });
  });
})();

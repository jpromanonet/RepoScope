(function () {
  'use strict';

  if (typeof Chart === 'undefined') {
    return;
  }

  var root = document.documentElement;
  var css = getComputedStyle(root);

  function token(name, fallback) {
    var value = css.getPropertyValue(name).trim();
    return value || fallback;
  }

  var palette = {
    cyan: token('--cyan', '#3DDCFF'),
    green: token('--green', '#3DDC97'),
    amber: token('--amber', '#F0B429'),
    rose: token('--rose', '#F07178'),
    violet: token('--violet', '#C084FC'),
    blue: token('--blue', '#7C9CFF'),
    muted: token('--muted', '#8B9BB4'),
    text: token('--text', '#E6EDF3'),
    line: token('--line', '#243040')
  };
  var cycle = [palette.cyan, palette.violet, palette.blue, palette.amber, palette.rose, palette.green, palette.muted];

  Chart.defaults.color = palette.text;
  Chart.defaults.borderColor = palette.line;
  Chart.defaults.font.family = token('--font', 'IBM Plex Sans, system-ui, sans-serif');

  function colorOf(name) {
    return palette[name] || name || palette.cyan;
  }

  function padColors(list, count) {
    var out = [];
    var i;
    for (i = 0; i < count; i += 1) {
      out.push(list[i] || cycle[i % cycle.length]);
    }
    return out;
  }

  document.querySelectorAll('[data-rs-chart]').forEach(function (canvas) {
    var raw = canvas.getAttribute('data-rs-chart');
    if (!raw) {
      return;
    }
    var spec;
    try {
      spec = JSON.parse(raw);
    } catch (err) {
      return;
    }

    var type = spec.type || 'bar';
    var labels = spec.labels || [];
    var colors = (spec.hex && spec.hex.length) ? spec.hex : (spec.colors || []).map(colorOf);
    var datasets = spec.datasets;
    if (!datasets) {
      var values = spec.values || [];
      var fillColor = colors.length ? padColors(colors, values.length) : colorOf(spec.color || 'cyan');
      datasets = [{
        label: spec.label || '',
        data: values,
        backgroundColor: fillColor,
        borderColor: type === 'line' ? colorOf(spec.color || 'cyan') : fillColor,
        borderWidth: type === 'line' ? 2 : 0,
        fill: false,
        tension: 0.35,
        pointRadius: type === 'line' ? 3 : 0,
        borderRadius: type === 'bar' ? 6 : 0
      }];
    } else {
      datasets = datasets.map(function (set) {
        var c = colorOf(set.color || 'cyan');
        return {
          label: set.label || '',
          data: set.data || [],
          borderColor: c,
          backgroundColor: c,
          borderWidth: 2,
          fill: false,
          tension: 0.35,
          pointRadius: 3
        };
      });
    }

    var isRound = type === 'pie' || type === 'doughnut';
    var legendRight = isRound && window.matchMedia('(min-width: 720px)').matches;
    var options = {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 0,
      layout: { padding: 4 },
      plugins: {
        legend: {
          display: isRound || datasets.length > 1,
          position: legendRight ? 'right' : 'bottom',
          labels: { boxWidth: 12, padding: 16 }
        }
      }
    };

    if (type === 'doughnut') {
      options.cutout = '58%';
    }

    if (type === 'bar' || type === 'line') {
      options.scales = {
        x: { ticks: { maxRotation: 40, minRotation: 0 }, grid: { display: false } },
        y: { beginAtZero: true, grid: { color: palette.line }, ticks: { precision: 0 } }
      };
      if (spec.max) {
        options.scales.y.max = spec.max;
      }
    }

    var chart = new Chart(canvas, { type: type, data: { labels: labels, datasets: datasets }, options: options });
    requestAnimationFrame(function () {
      chart.resize();
    });
  });
})();

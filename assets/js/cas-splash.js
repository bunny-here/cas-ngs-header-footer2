(function () {
  'use strict';

  var overlay = document.getElementById('cas-splash');
  if (!overlay) return;

  try {
    if (window.sessionStorage && sessionStorage.getItem('cas-ngs-splash-complete') === '1') {
      overlay.setAttribute('aria-hidden', 'true');
      return;
    }
  } catch (err) {}

  var logoTargets = [
    { topY: 100, botY: 412 },
    { topY: 200, botY: 300 },
    { topY: 160, botY: 340 },
    { topY: 88, botY: 400 },
    { topY: 160, botY: 340 },
    { topY: 200, botY: 300 },
    { topY: 100, botY: 412 }
  ];
  var nodes = [];
  var bonds = [];
  for (var i = 0; i < 7; i++) {
    nodes.push([
      overlay.querySelector('#n' + i + '-top'),
      overlay.querySelector('#n' + i + '-bot')
    ]);
    bonds.push(overlay.querySelector('#b' + i));
  }

  var mark = overlay.querySelector('#dna-mark');
  var clip = overlay.querySelector('#clip-rect');
  var startedAt = 0;
  var duration = 1250;
  var finished = false;

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function easeOut(value) {
    return 1 - Math.pow(1 - value, 3);
  }

  function finishSplash() {
    if (finished) return;
    finished = true;
    try {
      if (window.sessionStorage) sessionStorage.setItem('cas-ngs-splash-complete', '1');
    } catch (err) {}
    document.documentElement.classList.remove('cas-splash-active');
    document.body.classList.remove('cas-splash-active');
    document.body.classList.add('cas-splash-complete');
    overlay.setAttribute('aria-hidden', 'true');
    window.setTimeout(function () { overlay.style.display = 'none'; }, 300);
  }

  function draw(now) {
    var progress = clamp((now - startedAt) / duration, 0, 1);
    var logoMix = easeOut(clamp((progress - 0.2) / 0.65, 0, 1));
    var waveMix = easeOut(clamp(progress * 2.4, 0, 1)) * (1 - logoMix);
    var time = progress * Math.PI * 2;

    for (var i = 0; i < 7; i++) {
      var waveOffset = Math.sin(time + i * 0.7) * 130 * waveMix;
      var topY = 250 - waveOffset + (logoTargets[i].topY - (250 - waveOffset)) * logoMix;
      var botY = 250 + waveOffset + (logoTargets[i].botY - (250 + waveOffset)) * logoMix;
      if (nodes[i][0]) {
        nodes[i][0].setAttribute('cy', topY.toFixed(1));
        nodes[i][0].style.opacity = String(clamp(progress * 3, 0, 1));
      }
      if (nodes[i][1]) {
        nodes[i][1].setAttribute('cy', botY.toFixed(1));
        nodes[i][1].style.opacity = String(clamp(progress * 3, 0, 1));
      }
      if (bonds[i]) {
        bonds[i].setAttribute('y1', topY.toFixed(1));
        bonds[i].setAttribute('y2', botY.toFixed(1));
        bonds[i].style.opacity = String(clamp((progress - 0.12) * 2.5, 0, 1));
      }
    }

    if (mark) {
      var moveProgress = easeOut(clamp((progress - 0.45) / 0.48, 0, 1));
      var x = 270 + (55 - 270) * moveProgress;
      var scale = 0.5 + (0.2 - 0.5) * moveProgress;
      mark.setAttribute('transform', 'translate(' + x.toFixed(1) + ' 0) scale(' + scale.toFixed(3) + ')');
    }
    if (clip) clip.setAttribute('width', (650 * easeOut(clamp((progress - 0.55) / 0.4, 0, 1))).toFixed(1));

    if (progress < 1) window.requestAnimationFrame(draw);
    else finishSplash();
  }

  function startSplash() {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !mark || !clip) {
      finishSplash();
      return;
    }
    document.documentElement.classList.add('cas-splash-active');
    document.body.classList.add('cas-splash-active');
    startedAt = performance.now();
    window.requestAnimationFrame(draw);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startSplash, { once: true });
  } else {
    startSplash();
  }
})();

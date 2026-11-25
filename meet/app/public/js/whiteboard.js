/**
 * Younimatiq Meet — lightweight collaborative whiteboard
 * Tools: select, pen, eraser, sticky, text, rect, ellipse, arrow, line
 * Syncs via Socket.IO within the Meet room.
 */
(function (global) {
  'use strict';

  const COLORS = ['#111827', '#e11d48', '#ea580c', '#ca8a04', '#16a34a', '#2563eb', '#7c3aed', '#ffffff'];
  const STICKY_COLORS = ['#fef08a', '#bbf7d0', '#bfdbfe', '#fecaca', '#e9d5ff'];

  function uid() {
    return Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-4);
  }

  function createWhiteboard(opts) {
    const overlay = document.getElementById('wb-overlay');
    const canvas = document.getElementById('wb-canvas');
    const hint = document.getElementById('wb-hint');
    const zoomLabel = document.getElementById('wb-zoom-label');
    if (!overlay || !canvas) return null;

    const ctx = canvas.getContext('2d', { alpha: false });
    const state = {
      open: false,
      tool: 'pen',
      color: COLORS[0],
      size: 3,
      objects: [],
      selectedId: null,
      zoom: 1,
      panX: 0,
      panY: 0,
      drawing: null,
      dragging: null,
      history: [],
      future: [],
      applyingRemote: false,
      syncTimer: null,
      roomId: opts.roomId,
      myId: opts.myId,
      myName: opts.myName || 'Guest',
      socket: opts.socket,
    };

    function pushHistory() {
      state.history.push(JSON.stringify(state.objects));
      if (state.history.length > 40) state.history.shift();
      state.future = [];
    }

    function scheduleSync() {
      if (state.applyingRemote) return;
      clearTimeout(state.syncTimer);
      state.syncTimer = setTimeout(() => {
        if (!state.socket) return;
        state.socket.emit('whiteboard-sync', {
          roomId: state.roomId,
          objects: state.objects,
          from: state.myId,
          name: state.myName,
        });
      }, 80);
    }

    function resize() {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      const rect = canvas.parentElement.getBoundingClientRect();
      canvas.width = Math.max(1, Math.floor(rect.width * dpr));
      canvas.height = Math.max(1, Math.floor(rect.height * dpr));
      canvas.style.width = rect.width + 'px';
      canvas.style.height = rect.height + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      render();
    }

    function worldFromEvent(e) {
      const rect = canvas.getBoundingClientRect();
      const x = (e.clientX - rect.left - state.panX) / state.zoom;
      const y = (e.clientY - rect.top - state.panY) / state.zoom;
      return { x, y };
    }

    function hitTest(x, y) {
      for (let i = state.objects.length - 1; i >= 0; i--) {
        const o = state.objects[i];
        if (o.type === 'sticky' || o.type === 'text' || o.type === 'rect' || o.type === 'ellipse') {
          const w = o.w || 160;
          const h = o.h || 120;
          if (x >= o.x && x <= o.x + w && y >= o.y && y <= o.y + h) return o;
        }
        if (o.type === 'line' || o.type === 'arrow') {
          const dx = o.x2 - o.x1;
          const dy = o.y2 - o.y1;
          const len2 = dx * dx + dy * dy || 1;
          const t = Math.max(0, Math.min(1, ((x - o.x1) * dx + (y - o.y1) * dy) / len2));
          const px = o.x1 + t * dx;
          const py = o.y1 + t * dy;
          if ((px - x) ** 2 + (py - y) ** 2 < 64) return o;
        }
        if (o.type === 'path' && o.points && o.points.length) {
          for (const p of o.points) {
            if ((p.x - x) ** 2 + (p.y - y) ** 2 < 36) return o;
          }
        }
      }
      return null;
    }

    function eraseAt(x, y) {
      const before = state.objects.length;
      state.objects = state.objects.filter((o) => {
        if (o.type === 'path' && o.points) {
          return !o.points.some((p) => (p.x - x) ** 2 + (p.y - y) ** 2 < (18 / state.zoom) ** 2);
        }
        const hit = hitTest(x, y);
        return !(hit && hit.id === o.id);
      });
      if (state.objects.length !== before) {
        pushHistory();
        scheduleSync();
        render();
      }
    }

    function drawObject(o) {
      ctx.save();
      if (o.type === 'path' && o.points && o.points.length > 1) {
        ctx.strokeStyle = o.color || '#111';
        ctx.lineWidth = o.size || 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(o.points[0].x, o.points[0].y);
        for (let i = 1; i < o.points.length; i++) ctx.lineTo(o.points[i].x, o.points[i].y);
        ctx.stroke();
      } else if (o.type === 'sticky') {
        ctx.fillStyle = o.fill || '#fef08a';
        ctx.strokeStyle = 'rgba(0,0,0,0.08)';
        roundRect(o.x, o.y, o.w || 160, o.h || 140, 8);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = '#1f2937';
        ctx.font = '600 14px Inter, system-ui, sans-serif';
        wrapText(o.text || 'Sticky note', o.x + 12, o.y + 28, (o.w || 160) - 24, 18);
      } else if (o.type === 'text') {
        ctx.fillStyle = o.color || '#111827';
        ctx.font = `${o.size || 18}px Inter, system-ui, sans-serif`;
        wrapText(o.text || 'Text', o.x, o.y + (o.size || 18), o.w || 280, (o.size || 18) + 4);
      } else if (o.type === 'rect') {
        ctx.strokeStyle = o.color || '#111';
        ctx.lineWidth = o.size || 2;
        ctx.strokeRect(o.x, o.y, o.w || 1, o.h || 1);
      } else if (o.type === 'ellipse') {
        ctx.strokeStyle = o.color || '#111';
        ctx.lineWidth = o.size || 2;
        ctx.beginPath();
        ctx.ellipse(o.x + (o.w || 0) / 2, o.y + (o.h || 0) / 2, Math.abs((o.w || 0) / 2), Math.abs((o.h || 0) / 2), 0, 0, Math.PI * 2);
        ctx.stroke();
      } else if (o.type === 'line' || o.type === 'arrow') {
        ctx.strokeStyle = o.color || '#111';
        ctx.lineWidth = o.size || 2;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(o.x1, o.y1);
        ctx.lineTo(o.x2, o.y2);
        ctx.stroke();
        if (o.type === 'arrow') {
          const ang = Math.atan2(o.y2 - o.y1, o.x2 - o.x1);
          const len = 12;
          ctx.beginPath();
          ctx.moveTo(o.x2, o.y2);
          ctx.lineTo(o.x2 - len * Math.cos(ang - 0.4), o.y2 - len * Math.sin(ang - 0.4));
          ctx.moveTo(o.x2, o.y2);
          ctx.lineTo(o.x2 - len * Math.cos(ang + 0.4), o.y2 - len * Math.sin(ang + 0.4));
          ctx.stroke();
        }
      }
      if (state.selectedId === o.id) {
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = '#6366f1';
        ctx.lineWidth = 1;
        const b = bounds(o);
        ctx.strokeRect(b.x - 4, b.y - 4, b.w + 8, b.h + 8);
        ctx.setLineDash([]);
      }
      ctx.restore();
    }

    function bounds(o) {
      if (o.type === 'path' && o.points && o.points.length) {
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        o.points.forEach((p) => { minX = Math.min(minX, p.x); minY = Math.min(minY, p.y); maxX = Math.max(maxX, p.x); maxY = Math.max(maxY, p.y); });
        return { x: minX, y: minY, w: maxX - minX, h: maxY - minY };
      }
      if (o.type === 'line' || o.type === 'arrow') {
        return {
          x: Math.min(o.x1, o.x2),
          y: Math.min(o.y1, o.y2),
          w: Math.abs(o.x2 - o.x1),
          h: Math.abs(o.y2 - o.y1),
        };
      }
      return { x: o.x, y: o.y, w: o.w || 160, h: o.h || 120 };
    }

    function roundRect(x, y, w, h, r) {
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.arcTo(x + w, y, x + w, y + h, r);
      ctx.arcTo(x + w, y + h, x, y + h, r);
      ctx.arcTo(x, y + h, x, y, r);
      ctx.arcTo(x, y, x + w, y, r);
      ctx.closePath();
    }

    function wrapText(text, x, y, maxW, lineH) {
      const words = String(text).split(/\s+/);
      let line = '';
      let yy = y;
      for (const word of words) {
        const test = line ? line + ' ' + word : word;
        if (ctx.measureText(test).width > maxW && line) {
          ctx.fillText(line, x, yy);
          line = word;
          yy += lineH;
        } else line = test;
      }
      if (line) ctx.fillText(line, x, yy);
    }

    function render() {
      const rect = canvas.getBoundingClientRect();
      ctx.save();
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.fillStyle = '#f7f8fa';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.restore();

      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.save();
      ctx.translate(state.panX, state.panY);
      ctx.scale(state.zoom, state.zoom);

      // subtle grid in world space
      const grid = 24;
      const left = (-state.panX) / state.zoom;
      const top = (-state.panY) / state.zoom;
      const right = left + rect.width / state.zoom;
      const bottom = top + rect.height / state.zoom;
      ctx.strokeStyle = '#eceff4';
      ctx.lineWidth = 1 / state.zoom;
      ctx.beginPath();
      for (let x = Math.floor(left / grid) * grid; x <= right; x += grid) {
        ctx.moveTo(x, top); ctx.lineTo(x, bottom);
      }
      for (let y = Math.floor(top / grid) * grid; y <= bottom; y += grid) {
        ctx.moveTo(left, y); ctx.lineTo(right, y);
      }
      ctx.stroke();

      state.objects.forEach(drawObject);
      if (state.drawing) drawObject(state.drawing);
      ctx.restore();

      if (hint) hint.style.display = state.objects.length || state.drawing ? 'none' : 'block';
      if (zoomLabel) zoomLabel.textContent = Math.round(state.zoom * 100) + '%';
    }

    function setTool(tool) {
      state.tool = tool;
      overlay.querySelectorAll('.wb-tool[data-tool]').forEach((btn) => {
        btn.classList.toggle('active', btn.getAttribute('data-tool') === tool);
      });
      canvas.style.cursor = tool === 'select' ? 'default' : tool === 'eraser' ? 'cell' : 'crosshair';
    }

    function onPointerDown(e) {
      if (!state.open) return;
      canvas.setPointerCapture(e.pointerId);
      const p = worldFromEvent(e);
      if (state.tool === 'select') {
        const hit = hitTest(p.x, p.y);
        state.selectedId = hit ? hit.id : null;
        if (hit) {
          state.dragging = { id: hit.id, ox: p.x - (hit.x ?? hit.x1 ?? 0), oy: p.y - (hit.y ?? hit.y1 ?? 0), start: { ...hit } };
        }
        render();
        return;
      }
      if (state.tool === 'eraser') {
        eraseAt(p.x, p.y);
        state.drawing = { type: 'erase' };
        return;
      }
      if (state.tool === 'pen') {
        pushHistory();
        state.drawing = { id: uid(), type: 'path', color: state.color, size: state.size, points: [p], author: state.myId };
        return;
      }
      if (state.tool === 'sticky') {
        pushHistory();
        const text = window.prompt('Sticky note text', 'Idea') || 'Idea';
        state.objects.push({
          id: uid(), type: 'sticky', x: p.x, y: p.y, w: 160, h: 140,
          text, fill: STICKY_COLORS[Math.floor(Math.random() * STICKY_COLORS.length)], author: state.myId,
        });
        scheduleSync();
        render();
        return;
      }
      if (state.tool === 'text') {
        pushHistory();
        const text = window.prompt('Text', 'Label') || 'Label';
        state.objects.push({
          id: uid(), type: 'text', x: p.x, y: p.y, w: 280, h: 40,
          text, color: state.color, size: Math.max(14, state.size * 5), author: state.myId,
        });
        scheduleSync();
        render();
        return;
      }
      if (state.tool === 'rect' || state.tool === 'ellipse') {
        pushHistory();
        state.drawing = { id: uid(), type: state.tool, x: p.x, y: p.y, w: 0, h: 0, color: state.color, size: state.size, author: state.myId };
        return;
      }
      if (state.tool === 'line' || state.tool === 'arrow') {
        pushHistory();
        state.drawing = { id: uid(), type: state.tool, x1: p.x, y1: p.y, x2: p.x, y2: p.y, color: state.color, size: state.size, author: state.myId };
      }
    }

    function onPointerMove(e) {
      if (!state.open) return;
      const p = worldFromEvent(e);
      if (state.tool === 'eraser' && state.drawing) {
        eraseAt(p.x, p.y);
        return;
      }
      if (state.dragging && state.tool === 'select') {
        const o = state.objects.find((x) => x.id === state.dragging.id);
        if (!o) return;
        if (o.type === 'line' || o.type === 'arrow') {
          const dx = p.x - state.dragging.ox - o.x1;
          const dy = p.y - state.dragging.oy - o.y1;
          o.x1 += dx; o.y1 += dy; o.x2 += dx; o.y2 += dy;
        } else {
          o.x = p.x - state.dragging.ox;
          o.y = p.y - state.dragging.oy;
        }
        render();
        return;
      }
      if (!state.drawing || state.drawing.type === 'erase') return;
      if (state.drawing.type === 'path') {
        state.drawing.points.push(p);
      } else if (state.drawing.type === 'rect' || state.drawing.type === 'ellipse') {
        state.drawing.w = p.x - state.drawing.x;
        state.drawing.h = p.y - state.drawing.y;
      } else if (state.drawing.type === 'line' || state.drawing.type === 'arrow') {
        state.drawing.x2 = p.x;
        state.drawing.y2 = p.y;
      }
      render();
    }

    function onPointerUp() {
      if (state.dragging) {
        state.dragging = null;
        pushHistory();
        scheduleSync();
        return;
      }
      if (!state.drawing || state.drawing.type === 'erase') {
        state.drawing = null;
        return;
      }
      if (state.drawing.type === 'rect' || state.drawing.type === 'ellipse') {
        if (state.drawing.w < 0) { state.drawing.x += state.drawing.w; state.drawing.w = Math.abs(state.drawing.w); }
        if (state.drawing.h < 0) { state.drawing.y += state.drawing.h; state.drawing.h = Math.abs(state.drawing.h); }
      }
      state.objects.push(state.drawing);
      state.drawing = null;
      scheduleSync();
      render();
    }

    function undo() {
      if (!state.history.length) return;
      state.future.push(JSON.stringify(state.objects));
      state.objects = JSON.parse(state.history.pop());
      scheduleSync();
      render();
    }
    function redo() {
      if (!state.future.length) return;
      state.history.push(JSON.stringify(state.objects));
      state.objects = JSON.parse(state.future.pop());
      scheduleSync();
      render();
    }
    function clearBoard() {
      if (!state.objects.length) return;
      if (!window.confirm('Clear the whole whiteboard for everyone in this class?')) return;
      pushHistory();
      state.objects = [];
      scheduleSync();
      render();
    }
    function exportPng() {
      const link = document.createElement('a');
      link.download = 'younimatiq-whiteboard.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    }

    function open() {
      state.open = true;
      overlay.classList.add('open');
      resize();
      if (state.socket) {
        state.socket.emit('whiteboard-request', { roomId: state.roomId });
      }
      render();
    }
    function close() {
      state.open = false;
      overlay.classList.remove('open');
    }
    function toggle() {
      state.open ? close() : open();
      return state.open;
    }

    // UI wiring
    overlay.querySelectorAll('.wb-tool[data-tool]').forEach((btn) => {
      btn.addEventListener('click', () => setTool(btn.getAttribute('data-tool')));
    });
    overlay.querySelectorAll('.wb-swatch').forEach((el) => {
      el.addEventListener('click', () => {
        state.color = el.getAttribute('data-color');
        overlay.querySelectorAll('.wb-swatch').forEach((s) => s.classList.toggle('active', s === el));
      });
    });
    const sizeInput = document.getElementById('wb-size');
    if (sizeInput) sizeInput.addEventListener('input', () => { state.size = Number(sizeInput.value) || 3; });
    document.getElementById('wb-undo')?.addEventListener('click', undo);
    document.getElementById('wb-redo')?.addEventListener('click', redo);
    document.getElementById('wb-clear')?.addEventListener('click', clearBoard);
    document.getElementById('wb-export')?.addEventListener('click', exportPng);
    document.getElementById('wb-close')?.addEventListener('click', close);
    document.getElementById('wb-zoom-in')?.addEventListener('click', () => { state.zoom = Math.min(3, state.zoom + 0.1); render(); });
    document.getElementById('wb-zoom-out')?.addEventListener('click', () => { state.zoom = Math.max(0.4, state.zoom - 0.1); render(); });
    document.getElementById('wb-zoom-reset')?.addEventListener('click', () => { state.zoom = 1; state.panX = 0; state.panY = 0; render(); });

    canvas.addEventListener('pointerdown', onPointerDown);
    canvas.addEventListener('pointermove', onPointerMove);
    canvas.addEventListener('pointerup', onPointerUp);
    canvas.addEventListener('pointercancel', onPointerUp);
    window.addEventListener('resize', () => { if (state.open) resize(); });

    // Wheel zoom
    canvas.addEventListener('wheel', (e) => {
      if (!state.open) return;
      e.preventDefault();
      const delta = e.deltaY > 0 ? -0.08 : 0.08;
      state.zoom = Math.max(0.4, Math.min(3, state.zoom + delta));
      render();
    }, { passive: false });

    if (state.socket) {
      state.socket.on('whiteboard-sync', (payload) => {
        if (!payload || payload.from === state.myId) return;
        if (payload.roomId && payload.roomId !== state.roomId) return;
        state.applyingRemote = true;
        state.objects = Array.isArray(payload.objects) ? payload.objects : state.objects;
        state.applyingRemote = false;
        render();
      });
      state.socket.on('whiteboard-state', (payload) => {
        if (!payload || (payload.roomId && payload.roomId !== state.roomId)) return;
        state.applyingRemote = true;
        state.objects = Array.isArray(payload.objects) ? payload.objects : [];
        state.applyingRemote = false;
        render();
      });
    }

    setTool('pen');
    const firstSwatch = overlay.querySelector('.wb-swatch');
    if (firstSwatch) firstSwatch.classList.add('active');

    return { open, close, toggle, isOpen: () => state.open };
  }

  global.YouniWhiteboard = { create: createWhiteboard };
})(window);

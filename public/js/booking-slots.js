(function () {
  function formatSlotLabel(utcIso, tz) {
    if (!utcIso) return '';
    const start = new Date(utcIso);
    if (Number.isNaN(start.getTime())) return '';
    const end = new Date(start.getTime() + 60 * 60000);
    const fmt = { hour: 'numeric', minute: '2-digit', timeZone: tz };
    try {
      return new Intl.DateTimeFormat(undefined, fmt).format(start) + ' – ' + new Intl.DateTimeFormat(undefined, fmt).format(end);
    } catch (e) {
      return '';
    }
  }

  function fillDaySelect(select, slots, timezone) {
    const current = select.value;
    const wasCustom = current === '__custom__';
    select.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select a recommended time';
    select.appendChild(placeholder);

    (slots || []).forEach(function (slot) {
      const opt = document.createElement('option');
      opt.value = slot.value;
      opt.textContent = formatSlotLabel(slot.utc_iso, timezone) || slot.label;
      select.appendChild(opt);
    });

    const custom = document.createElement('option');
    custom.value = '__custom__';
    custom.textContent = 'Choose a different time';
    select.appendChild(custom);

    if (wasCustom) {
      select.value = '__custom__';
    } else if (current && Array.prototype.some.call(select.options, function (o) { return o.value === current; })) {
      select.value = current;
    }
  }

  function toggleCustom(select) {
    const wrap = select.parentElement && select.parentElement.querySelector('[data-custom-time-wrap]');
    const input = wrap && wrap.querySelector('input[type="time"]');
    const isCustom = select.value === '__custom__';
    if (wrap) wrap.style.display = isCustom ? '' : 'none';
    if (input) {
      input.disabled = !isCustom || select.disabled;
      input.required = isCustom && !select.disabled;
      if (isCustom && input.value) {
        select.dataset.customValue = input.value;
      }
    }
  }

  async function loadWeekdaySlots(timezone, startDate) {
    if (!timezone || !startDate) return;
    const url = '/api/consultation-slots?group_by_weekday=1&timezone=' + encodeURIComponent(timezone) + '&week_of=' + encodeURIComponent(startDate);
    const res = await fetch(url);
    if (!res.ok) throw new Error('Could not load class times');
    const data = await res.json();
    document.querySelectorAll('[data-day-time-select]').forEach(function (select) {
      const day = select.getAttribute('data-day');
      fillDaySelect(select, (data.by_weekday && data.by_weekday[day]) || [], timezone);
      toggleCustom(select);
    });
  }

  function bind() {
    const tz = document.getElementById('timezone') || document.querySelector('[name="timezone"]');
    const start = document.getElementById('schedule_start_date') || document.querySelector('[name="start_date"]');
    const refresh = function () {
      if (!tz || !start || !tz.value || !start.value) return;
      loadWeekdaySlots(tz.value, start.value).catch(function (err) {
        console.error(err);
      });
    };

    if (tz) tz.addEventListener('change', refresh);
    if (start) start.addEventListener('change', refresh);
    refresh();

    document.addEventListener('change', function (e) {
      if (e.target && e.target.matches('[data-day-time-select]')) {
        toggleCustom(e.target);
      }
      if (e.target && e.target.matches('[data-custom-time]')) {
        const select = e.target.closest('[data-day-time-row]') && e.target.closest('[data-day-time-row]').querySelector('[data-day-time-select]');
        if (select) select.dataset.customValue = e.target.value;
      }
    });

    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        form.querySelectorAll('[data-day-time-select]').forEach(function (select) {
          if (select.value !== '__custom__') return;
          const row = select.closest('[data-day-time-row]');
          const input = row && row.querySelector('[data-custom-time]');
          if (input && input.value) {
            select.disabled = true;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = select.name;
            hidden.value = input.value;
            form.appendChild(hidden);
          }
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', bind);
})();

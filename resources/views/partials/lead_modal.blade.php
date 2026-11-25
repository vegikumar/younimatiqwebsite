<div id="leadModal" class="lead-modal-overlay">
      <div class="lead-modal-panel">
        <button id="leadModalClose" class="mclose" aria-label="Close"><span class="material-symbols-outlined">close</span></button>
        <div class="lead-modal-header">
          <h3>Book a Free Class</h3>
          <p>Tell us a bit about your child to book a free consultation for the selected course.</p>
          <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 12px; color: #38B91F; font-size: 13px; font-weight: 700;">
            <span class="material-symbols-outlined" style="font-size: 16px;">how_to_reg</span>
            <span>All tutors are background-checked</span>
          </div>
        </div>

        @if ($errors->hasBag('consultation') && $errors->consultation->any())
          <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
              <ul style="margin:0; padding-left:20px;">
                  @foreach ($errors->consultation->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
          <script>
              document.addEventListener('DOMContentLoaded', function() {
                  const modal = document.getElementById('leadModal');
                  if (modal) {
                      modal.classList.add('open');
                  }
              });
          
    // Prevent casual screenshot/copy capture of the booking form.
    (function () {
      const panel = document.querySelector('#leadModal .lead-modal-panel');
      if (!panel) return;

      ['contextmenu', 'dragstart', 'copy', 'cut'].forEach(function (evt) {
        panel.addEventListener(evt, function (e) { e.preventDefault(); });
      });

      function guard() {
        if (!document.getElementById('leadModal').classList.contains('open')) return;
        panel.classList.add('is-capture-guarded');
      }
      function release() {
        panel.classList.remove('is-capture-guarded');
      }

      window.addEventListener('blur', guard);
      window.addEventListener('focus', release);
      document.addEventListener('visibilitychange', function () {
        document.hidden ? guard() : release();
      });

      window.addEventListener('keydown', function (e) {
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
          guard();
          try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText('Screenshots of this booking form are not permitted.').catch(function () {});
            }
          } catch (err) {}
          setTimeout(release, 1800);
        }
      });

      const observer = new MutationObserver(function () {
        if (!document.getElementById('leadModal').classList.contains('open')) {
          release();
        }
      });
      observer.observe(document.getElementById('leadModal'), { attributes: true, attributeFilter: ['class'] });
    })();
</script>
        @endif
        @php
            $modalCategories = \Illuminate\Support\Facades\Cache::remember('lead_modal_categories', now()->addMinutes(30), function () {
                return \App\Models\Category::query()
                    ->where('is_active', true)
                    ->whereHas('courses', fn ($q) => $q->where('is_active', true))
                    ->with(['courses' => function ($q) {
                        $q->select('courses.id', 'courses.title')
                            ->where('is_active', true)
                            ->orderBy('title');
                    }])
                    ->get(['id', 'name', 'slug'])
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'courses' => $c->courses->map(fn ($co) => [
                            'id' => $co->id,
                            'title' => $co->title,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();
            });
        @endphp
        <form id="leadForm" action="{{ route('booking.consultation.store') }}" method="POST" autocomplete="on">
          @csrf
          <input type="hidden" name="booking_type" id="booking_type" value="consultation">
          <div class="lead-form-scroll" data-scroll-native>
          <div id="child1-section">
            <!-- Grade selection -->
            <div class="form-group" id="grade-group-c1">
              <label>Which grade is your child in?</label>
              <div class="grade-grid">
                <label class="grade-btn"><input type="radio" name="grade_c1" value="1" required><span>1</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="2"><span>2</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="3"><span>3</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="4"><span>4</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="5"><span>5</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="6"><span>6</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="7"><span>7</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="8"><span>8</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="9"><span>9</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="10"><span>10</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="11"><span>11</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c1" value="12"><span>12</span></label>
              </div>
            </div>

            <!-- Curriculum -->
            <div class="form-group" id="board-group-c1">
              <label>What curriculum does your child follow?</label>
              <select name="board_c1" class="form-control" required>
                <option value="" disabled selected>Select curriculum</option>
                @if(session('display_currency', 'USD') === 'INR')
                    <option value="CBSE">CBSE</option>
                    <option value="ICSE">ICSE</option>
                    <option value="IB">IB</option>
                    <option value="IGCSE/Cambridge">IGCSE/Cambridge</option>
                    <option value="State/Provincial Curriculum">State/Provincial Curriculum</option>
                    <option value="AP">AP</option>
                    <option value="Homeschool">Homeschool</option>
                @else
                    <option value="State/Provincial Curriculum">State/Provincial Curriculum</option>
                    <option value="AP">AP</option>
                    <option value="Homeschool">Homeschool</option>
                    <option value="IB">IB</option>
                    <option value="IGCSE/Cambridge">IGCSE/Cambridge</option>
                    <option value="CBSE">CBSE</option>
                    <option value="ICSE">ICSE</option>
                @endif
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex:2;">
                <label>What's your child's name?</label>
                <input type="text" name="name_c1" id="leadChildName1" class="form-control" placeholder="Child's Name" autocomplete="given-name" required>
              </div>
              <div class="form-group" style="flex:1;">
                <label>Age</label>
                <input type="number" name="age_c1" id="leadChildAge1" class="form-control" placeholder="Age" min="4" max="19" autocomplete="off" required>
              </div>
            </div>

            <!-- Category -->
            <div class="form-group category-group" style="display: none;">
              <label>What category are you interested in?</label>
              <select name="category_c1" class="form-control" onchange="updateCourses('c1')">
                <option value="" disabled selected hidden>Select category</option>
              </select>
            </div>

            <!-- Courses -->
            <div class="form-group" id="course-group-c1">
              <label>Course you are interested in</label>
              <select name="courses_c1" class="form-control" required>
                <option value="" disabled selected hidden>Select course</option>
              </select>
            </div>
          </div>

          <div class="form-group add-child-toggle" style="margin-top:16px;">
            <label class="course-check" style="font-weight:700; color:var(--blue);">
              <input type="checkbox" id="child2Toggle">
              <span>Add 2nd Child (Optional)</span>
            </label>
          </div>

          <div id="child2-section" style="display:none; padding-top: 20px; border-top: 1px solid var(--color-outline-variant); margin-top:16px;">
            <h4>Child 2 Details</h4>
            <div class="form-group" id="grade-group-c2">
              <label>Which grade is your child in?</label>
              <div class="grade-grid">
                <label class="grade-btn"><input type="radio" name="grade_c2" value="1"><span>1</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="2"><span>2</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="3"><span>3</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="4"><span>4</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="5"><span>5</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="6"><span>6</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="7"><span>7</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="8"><span>8</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="9"><span>9</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="10"><span>10</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="11"><span>11</span></label>
                <label class="grade-btn"><input type="radio" name="grade_c2" value="12"><span>12</span></label>
              </div>
            </div>

            <div class="form-group" id="board-group-c2">
              <label>What curriculum does your child follow?</label>
              <select name="board_c2" class="form-control">
                <option value="" disabled selected>Select curriculum</option>
                @if(session('display_currency', 'USD') === 'INR')
                    <option value="CBSE">CBSE</option>
                    <option value="ICSE">ICSE</option>
                    <option value="IB">IB</option>
                    <option value="IGCSE/Cambridge">IGCSE/Cambridge</option>
                    <option value="State/Provincial Curriculum">State/Provincial Curriculum</option>
                    <option value="AP">AP</option>
                    <option value="Homeschool">Homeschool</option>
                @else
                    <option value="State/Provincial Curriculum">State/Provincial Curriculum</option>
                    <option value="AP">AP</option>
                    <option value="Homeschool">Homeschool</option>
                    <option value="IB">IB</option>
                    <option value="IGCSE/Cambridge">IGCSE/Cambridge</option>
                    <option value="CBSE">CBSE</option>
                    <option value="ICSE">ICSE</option>
                @endif
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex:2;">
                <label>What's your child's name?</label>
                <input type="text" name="name_c2" id="leadChildName2" class="form-control" placeholder="Child's Name" autocomplete="off">
              </div>
              <div class="form-group" style="flex:1;">
                <label>Age</label>
                <input type="number" name="age_c2" class="form-control" placeholder="Age" min="4" max="19">
              </div>
            </div>

            <div class="form-group category-group" style="display: none;">
              <label>What category are you interested in?</label>
              <select name="category_c2" class="form-control" onchange="updateCourses('c2')">
                <option value="" disabled selected hidden>Select category</option>
              </select>
            </div>

            <div class="form-group">
              <label>Course you are interested in</label>
              <select name="courses_c2" class="form-control">
                <option value="" disabled selected hidden>Select course</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label>Parent/Guardian Phone Number</label>
              <input type="tel" name="phone" id="leadModalPhone" class="form-control" placeholder="Phone Number" autocomplete="tel" inputmode="tel">
            </div>
            <div class="form-group" style="flex:1;">
              <label>WhatsApp Number</label>
              <input type="tel" name="whatsapp_number" id="leadModalWhatsapp" class="form-control" placeholder="WhatsApp Number" autocomplete="tel" inputmode="tel">
            </div>
          </div>
          <div class="form-group">
            <label>Parent/Guardian Email ID</label>
            <input type="email" name="email" id="leadModalEmail" class="form-control" placeholder="Email Address" autocomplete="email" inputmode="email" required pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Please enter a valid email address (e.g., user@example.com)">
          </div>

          <div style="margin: 24px 0; border-top: 1px solid var(--color-outline-variant);"></div>
          
          <div class="form-group">
            <label>Your timezone</label>
            <select name="timezone" id="parentTimezoneInput" class="form-control" required>
              @foreach(\App\Services\TimezoneService::commonTimezones() as $tzId => $tzLabel)
                <option value="{{ $tzId }}">{{ $tzLabel }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label>Preferred Date</label>
              <input type="date" name="scheduled_date" id="consultDate" class="form-control" autocomplete="off" required>
            </div>
            <div class="form-group" style="flex:1;">
              <label>Preferred Time</label>
              <select name="scheduled_time" id="consultTime" class="form-control" required>
                <option value="" disabled selected>Select a date first</option>
              </select>
            </div>
          </div>
          <input type="hidden" name="custom_slot" id="consultCustomSlot" value="0">
          <div class="form-group" id="consultCustomWrap" style="display:none;">
            <label>Your preferred time</label>
            <input type="time" name="custom_scheduled_time" id="consultCustomTime" class="form-control" step="300">
          </div>

          </div>
          <div class="lead-form-actions" style="flex:0 0 auto;display:block;visibility:visible;background:#fff;">
            <button type="submit" class="btn btn-primary btn-block" id="leadFormSubmit" style="display:block;width:100%;min-height:48px;">Submit Details</button>
          </div>
        </form>
      </div>
    </div>
<style>
    .iti { width: 100%; max-width: 100%; }
    .iti input:-webkit-autofill,
    .iti input:-webkit-autofill:hover,
    .iti input:-webkit-autofill:focus {
        -webkit-text-fill-color: #111827;
        box-shadow: 0 0 0 1000px #fff inset;
    }
    .iti__country-list {
      color: #1f2937;
      text-align: left;
      z-index: 10000;
      max-width: min(360px, calc(100vw - 24px));
    }
    .iti--container {
      z-index: 10000 !important;
    }
    .iti__selected-dial-code {
      color: #111827;
      font-weight: 600;
    }
    .lead-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.68);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      z-index: 120;
      display: none;
      align-items: center;
      justify-content: flex-start;
      flex-direction: column;
      padding: max(12px, env(safe-area-inset-top, 12px)) 12px max(16px, env(safe-area-inset-bottom, 16px));
      box-sizing: border-box;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
      touch-action: pan-y;
    }
    .lead-modal-overlay.open {
      display: flex;
    }
    body.modal-open {
      overflow: hidden !important;
      overscroll-behavior: none;
    }
    html.modal-open {
      overflow: hidden !important;
      overscroll-behavior: none;
    }
    .lead-modal-panel {
      background: #fff;
      color: #111827;
      border-radius: 24px;
      width: min(640px, 100%);
      max-width: 100%;
      max-height: calc(100dvh - 24px);
      max-height: calc(100vh - 24px);
      margin: 8px auto;
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      padding: 0;
      box-sizing: border-box;
      border: 1px solid rgba(255, 255, 255, 0.6);
      box-shadow: 0 32px 80px -20px rgba(15, 23, 42, 0.45), 0 0 0 1px rgba(15, 23, 42, 0.04);
      flex-shrink: 1;
      min-height: 0;
    }
    #leadForm {
      flex: 1 1 0%;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .lead-form-scroll {
      flex: 1 1 0%;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
      touch-action: pan-y;
      padding: 24px 30px 16px;
    }
    .lead-form-actions {
      flex: 0 0 auto;
      padding: 12px 30px max(18px, env(safe-area-inset-bottom, 18px));
      background: #fff;
      border-top: 1px solid #e5eaf2;
      box-shadow: 0 -10px 24px -14px rgba(15, 23, 42, 0.18);
      display: block;
      visibility: visible;
      z-index: 6;
    }
    .mclose {
      position: absolute;
      top: 16px;
      right: 16px;
      z-index: 3;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      padding: 0;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      background: rgba(255, 255, 255, 0.92);
      color: #475569;
      cursor: pointer;
      backdrop-filter: blur(6px);
      transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }
    .mclose:hover {
      background: #fff;
      color: #7130D8;
      transform: scale(1.04);
    }
    .lead-modal-header {
      position: relative;
      padding: 30px 30px 22px;
      background: linear-gradient(135deg, #f6f2ff 0%, #eef6ff 55%, #f4fbf7 100%);
      border-bottom: 1px solid #e5eaf2;
      text-align: left;
    }
    .lead-modal-header::after {
      content: '';
      position: absolute;
      left: 30px;
      bottom: -1px;
      width: 84px;
      height: 3px;
      border-radius: 999px;
      background: linear-gradient(90deg, #7130D8, #3b82f6);
    }
    .lead-modal-header h3 {
      font-family: 'Sora', sans-serif;
      font-size: 26px;
      line-height: 1.2;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #1e1b4b;
      margin: 0;
    }
    .lead-modal-header p {
      font-size: 14px;
      line-height: 1.55;
      color: #64748b;
      margin-top: 8px;
      margin-bottom: 0;
      max-width: 46ch;
    }
    .lead-modal-header > div {
      justify-content: flex-start !important;
      background: rgba(255, 255, 255, 0.7);
      border: 1px solid #d9f0d4;
      width: fit-content;
      padding: 6px 12px;
      border-radius: 999px;
      margin-top: 14px !important;
    }
    @media (min-width: 480px) {
      .lead-modal-overlay { padding: 40px 20px; }
    }
    @media (max-width: 640px) {
      .lead-modal-overlay { padding: 6px 6px max(6px, env(safe-area-inset-bottom, 6px)); }
      .lead-modal-panel {
        border-radius: 18px;
        width: 100%;
        max-height: calc(100vh - 12px);
        max-height: calc(100dvh - 12px);
        height: calc(100vh - 12px);
        height: calc(100dvh - 12px);
        margin: 0 auto;
      }
      .lead-modal-header { padding: 20px 16px 14px; flex: 0 0 auto; }
      .lead-modal-header h3 { font-size: 20px; }
      .lead-modal-header p { font-size: 13px; }
      .lead-form-scroll { padding: 14px 14px 10px; }
      .lead-form-actions {
        padding: 10px 14px max(12px, env(safe-area-inset-bottom, 12px));
      }
      #leadForm button[type="submit"] {
        min-height: 48px;
        height: 48px;
        font-size: 15px;
      }
    }
    .form-group { margin-bottom: 18px; min-width: 0; }
    .form-group > label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.01em;
      margin-bottom: 7px;
      color: #334155;
    }
    .form-control {
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
      height: 46px;
      padding: 0 14px;
      border-radius: 12px;
      border: 1px solid #dbe2ea;
      background: #f8fafc;
      color: #0f172a;
      font-family: inherit;
      font-size: 14px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .form-control:hover { background: #fff; border-color: #cbd5e1; }
    .form-control:focus {
      outline: none;
      background: #fff;
      border-color: #7130D8;
      box-shadow: 0 0 0 4px rgba(113, 48, 216, 0.12);
    }
    .form-control::placeholder { color: #94a3b8; }
    #leadModal select.form-control {
      appearance: none !important;
      -webkit-appearance: none !important;
      -moz-appearance: none !important;
      cursor: pointer;
      padding-right: 44px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
      background-repeat: no-repeat !important;
      background-position: right 16px center !important;
      background-size: 18px !important;
    }
    #leadModal select.form-control::-ms-expand {
      display: none;
    }
    #leadModal select.form-control:focus {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%237130D8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    }
    input[type="date"].form-control {
      cursor: pointer;
    }
    #leadForm button[type="submit"] {
      width: 100%;
      min-height: 52px;
      height: 52px;
      border: 0;
      border-radius: 14px;
      background: linear-gradient(135deg, #7130D8 0%, #5b21b6 55%, #4338ca 100%);
      color: #fff;
      font-size: 16px;
      font-weight: 800;
      letter-spacing: 0.01em;
      cursor: pointer;
      display: block;
      visibility: visible;
      opacity: 1;
      box-shadow: 0 14px 30px -10px rgba(113, 48, 216, 0.55);
      transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }
    #leadForm button[type="submit"]:hover {
      transform: translateY(-1px);
      filter: brightness(1.04);
      box-shadow: 0 18px 36px -10px rgba(113, 48, 216, 0.6);
    }
    #leadForm button[type="submit"]:active {
      transform: translateY(0);
    }
    #leadForm button[type="submit"]:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }
    .grade-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }
    @media (min-width: 480px) {
      .grade-grid { grid-template-columns: repeat(6, 1fr); }
    }
    .grade-btn input { display: none; }
    .grade-btn span {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 42px;
      border-radius: 11px;
      border: 1px solid #dbe2ea;
      background: #f8fafc;
      color: #334155;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }
    .grade-btn:hover span {
      border-color: #c4b5fd;
      background: #fff;
    }
    .grade-btn input:checked + span {
      background: linear-gradient(135deg, #7130D8, #5b21b6);
      color: #fff;
      border-color: #7130D8;
      box-shadow: 0 6px 16px -6px rgba(113, 48, 216, 0.55);
    }
    .course-checkbox-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 8px;
    }
    @media (min-width: 480px) {
      .course-checkbox-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .course-check {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      cursor: pointer;
      min-width: 0;
    }
    .form-row {
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    @media (min-width: 480px) {
      .form-row {
        flex-direction: row;
        gap: 12px;
      }
    }

    /* Screenshot / copy deterrence for the booking modal */
    #leadModal .lead-modal-panel {
      -webkit-user-select: none;
      user-select: none;
      -webkit-touch-callout: none;
    }
    #leadModal .lead-modal-panel input,
    #leadModal .lead-modal-panel textarea,
    #leadModal .lead-modal-panel select {
      -webkit-user-select: text;
      user-select: text;
    }
    #leadModal .lead-modal-panel.is-capture-guarded > * {
      filter: blur(16px);
      transition: filter 0.12s ease;
    }
    #leadModal .lead-modal-panel.is-capture-guarded::after {
      content: 'Booking form protected — screenshots are not permitted';
      position: absolute;
      inset: 0;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      text-align: center;
      font-family: 'Sora', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: #fff;
      background: rgba(15, 23, 42, 0.72);
      backdrop-filter: blur(2px);
    }
    @media print {
      #leadModal { display: none !important; }
      #leadModal .lead-modal-panel { display: none !important; }
    }
</style>

<script defer>
// Lead Capture Modal Logic
    const leadModal = document.getElementById('leadModal');
    const leadModalClose = document.getElementById('leadModalClose');
    const leadForm = document.getElementById('leadForm');
    const child2Toggle = document.getElementById('child2Toggle');
    const child2Section = document.getElementById('child2-section');

    const phoneInputModal = document.getElementById('leadModalPhone');
    const whatsappInputModal = document.getElementById('leadModalWhatsapp');
    let itiPhoneModal, itiWhatsappModal;
    let phoneInputsReady = false;
    let phoneInputsLoading = null;
    const detectedCountry = @json(strtolower((string) (session('country_code') ?: '')));

    function loadStylesheet(href, id) {
        const existing = document.getElementById(id);
        if (existing) {
            return Promise.resolve();
        }
        return new Promise(function (resolve, reject) {
            const link = document.createElement('link');
            link.id = id;
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    function loadScript(src, id) {
        const existing = document.getElementById(id);
        if (existing) {
            if (id === 'intl-tel-input-js' && window.intlTelInput) {
                return Promise.resolve();
            }
            return new Promise(function (resolve, reject) {
                existing.addEventListener('load', resolve, { once: true });
                existing.addEventListener('error', reject, { once: true });
            });
        }
        return new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.id = id;
            script.src = src;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        });
    }

    function resolveCountry(callback) {
        if (detectedCountry && /^[a-z]{2}$/.test(detectedCountry)) {
            callback(detectedCountry);
            return;
        }

        // Prefer same-origin geo if available, then public HTTPS IP APIs
        fetch('/geo/country', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) {
                const code = (data.country_code || data.countryCode || '').toString().toLowerCase();
                callback(/^[a-z]{2}$/.test(code) ? code : 'us');
            })
            .catch(function () {
                return fetch('https://ipapi.co/json/')
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        callback((data.country_code || 'us').toLowerCase());
                    });
            })
            .catch(function () {
                callback('us');
            });
    }

    function phoneInputOptions() {
        return {
            initialCountry: 'auto',
            preferredCountries: ['us', 'in', 'au', 'gb', 'ae', 'sg', 'ca'],
            nationalMode: false,
            formatOnDisplay: true,
            autoPlaceholder: 'aggressive',
            separateDialCode: true,
            dropdownContainer: document.body,
            geoIpLookup: resolveCountry,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        };
    }

    function initPhoneInputs() {
        if (phoneInputsReady || !phoneInputModal || !window.intlTelInput) {
            return;
        }

        // Destroy any prior instance if re-opened after partial DOM swaps
        if (phoneInputModal.getAttribute('data-intl-tel-input-id') != null && window.intlTelInputGlobals) {
            try {
                const existing = window.intlTelInputGlobals.getInstance(phoneInputModal);
                if (existing) existing.destroy();
            } catch (e) {}
        }

        itiPhoneModal = window.intlTelInput(phoneInputModal, phoneInputOptions());
        phoneInputModal.setAttribute('autocomplete', 'tel');
        phoneInputModal.setAttribute('inputmode', 'tel');

        if (whatsappInputModal) {
            if (whatsappInputModal.getAttribute('data-intl-tel-input-id') != null && window.intlTelInputGlobals) {
                try {
                    const existingWa = window.intlTelInputGlobals.getInstance(whatsappInputModal);
                    if (existingWa) existingWa.destroy();
                } catch (e) {}
            }
            itiWhatsappModal = window.intlTelInput(whatsappInputModal, phoneInputOptions());
            whatsappInputModal.setAttribute('autocomplete', 'tel');
            whatsappInputModal.setAttribute('inputmode', 'tel');

            phoneInputModal.addEventListener('countrychange', function () {
                if (!itiPhoneModal || !itiWhatsappModal) return;
                const country = itiPhoneModal.getSelectedCountryData();
                if (country && country.iso2) {
                    itiWhatsappModal.setCountry(country.iso2);
                }
            });
        }

        phoneInputsReady = true;
    }

    function ensurePhoneInputs() {
        if (phoneInputsReady && itiPhoneModal) {
            return Promise.resolve();
        }
        if (phoneInputsLoading) {
            return phoneInputsLoading;
        }

        phoneInputsLoading = loadStylesheet(
            'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css',
            'intl-tel-input-css'
        ).then(function () {
            return loadScript(
                'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js',
                'intl-tel-input-js'
            );
        }).then(function () {
            initPhoneInputs();
            phoneInputsLoading = null;
        }).catch(function (error) {
            phoneInputsLoading = null;
            console.error('Phone input library failed to load', error);
        });

        return phoneInputsLoading;
    }

    if (leadForm) {
      leadForm.addEventListener('submit', function(e) {
        function normalizeField(iti, input) {
          if (!iti || !input) return true;
          if (input.value.trim() === '') {
            if (input.hasAttribute('required')) {
              input.style.borderColor = 'red';
              return false;
            }
            return true;
          }
          // If utils not ready yet, still force E.164 via getNumber when possible
          if (window.intlTelInputUtils && !iti.isValidNumber()) {
            input.style.borderColor = 'red';
            return false;
          }
          input.style.borderColor = '';
          const full = iti.getNumber();
          if (full) input.value = full;
          return true;
        }

        const phoneOk = normalizeField(itiPhoneModal, phoneInputModal);
        const waOk = normalizeField(itiWhatsappModal, whatsappInputModal);
        if (!phoneOk || !waOk) {
          e.preventDefault();
        }
      });
    }

    const dbCategories = @json($modalCategories);

    function populateCategories(prefix) {
        const catSelect = document.querySelector(`[name="category_${prefix}"]`);
        if (!catSelect) return;
        catSelect.innerHTML = '<option value="" disabled selected hidden>Select category</option>';
        dbCategories.forEach(cat => {
            catSelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
        });
    }

    function updateCourses(prefix, categoryId = null) {
        const catSelect = document.querySelector(`[name="category_${prefix}"]`);
        const courseSelect = document.querySelector(`[name="courses_${prefix}"]`);
        if(!courseSelect) return;
        
        let selectedCatId = categoryId;
        if (!selectedCatId && catSelect) {
            selectedCatId = catSelect.value;
        }
        
        courseSelect.innerHTML = '<option value="" disabled selected hidden>Select course</option>';
        
        if (selectedCatId) {
            const category = dbCategories.find(c => c.id == selectedCatId);
            if (category && category.courses) {
                category.courses.forEach(course => {
                    courseSelect.innerHTML += `<option value="${course.title}">${course.title}</option>`;
                });
            }
        }
    }

    let currentFormType = null;
    const openLeadModal = (e, type = 'universal', categoryId = null, courseTitle = null) => {
      if(e) e.preventDefault();
      currentFormType = type;
      
      const categoryGroups = document.querySelectorAll('.category-group');
      
      if (type === 'robotics') {
          categoryGroups.forEach(g => g.style.display = 'none');
          const roboticsCat = dbCategories.find(c => c.slug.includes('robotic'));
          if (roboticsCat) {
              updateCourses('c1', roboticsCat.id);
              updateCourses('c2', roboticsCat.id);
          }
          document.getElementById('booking_type').value = 'consultation';
          document.querySelector('.lead-modal-header h3').innerText = 'Book a free consultation';
          setConsultDateMin(48);
      } else if (type === 'minecraft') {
          categoryGroups.forEach(g => g.style.display = 'none');
          const mcCat = dbCategories.find(c => c.slug.includes('minecraft'));
          if (mcCat) {
              updateCourses('c1', mcCat.id);
              updateCourses('c2', mcCat.id);
          }
          document.getElementById('booking_type').value = 'consultation';
          document.querySelector('.lead-modal-header h3').innerText = 'Book a free consultation';
          setConsultDateMin(48);
      } else if (type === 'trial' || type === 'free_class') {
          categoryGroups.forEach(g => g.style.display = 'block');
          populateCategories('c1');
          populateCategories('c2');
          document.getElementById('booking_type').value = 'free_class';
          document.querySelector('.lead-modal-header h3').innerText = 'Book a free trial class';
          setConsultDateMin(48);
      } else {
          categoryGroups.forEach(g => g.style.display = 'block');
          populateCategories('c1');
          populateCategories('c2');
          
          if (categoryId) {
              const catSelect1 = document.querySelector('[name="category_c1"]');
              if (catSelect1) catSelect1.value = categoryId;
              
              const catSelect2 = document.querySelector('[name="category_c2"]');
              if (catSelect2) catSelect2.value = categoryId;
              
              updateCourses('c1', categoryId);
              updateCourses('c2', categoryId);
              
              if (courseTitle) {
                  const courseSelect1 = document.querySelector('[name="courses_c1"]');
                  if (courseSelect1) courseSelect1.value = courseTitle;
                  
                  const courseSelect2 = document.querySelector('[name="courses_c2"]');
                  if (courseSelect2) courseSelect2.value = courseTitle;
              }
              
              document.getElementById('grade-group-c1').style.display = 'none';
              document.getElementById('board-group-c1').style.display = 'none';
              document.getElementById('course-group-c1').style.display = 'none';
              
              if(document.getElementById('grade-group-c2')) document.getElementById('grade-group-c2').style.display = 'none';
              if(document.getElementById('board-group-c2')) document.getElementById('board-group-c2').style.display = 'none';
              
              categoryGroups.forEach(g => g.style.display = 'none');
              
              const grade1Radio = document.querySelector('input[name="grade_c1"]');
              if(grade1Radio) grade1Radio.removeAttribute('required');
              const grade1Opt = document.querySelector('input[name="grade_c1"][value="1"]');
              if(grade1Opt) grade1Opt.checked = true;
              
              const board1 = document.querySelector('[name="board_c1"]');
              if(board1) {
                  board1.removeAttribute('required');
                  board1.value = 'Other';
              }
              
              document.getElementById('booking_type').value = 'free_class';
              document.querySelector('.lead-modal-header h3').innerText = 'Book a free trial class';
              setConsultDateMin(48);
          } else {
              document.getElementById('grade-group-c1').style.display = 'block';
              document.getElementById('board-group-c1').style.display = 'block';
              document.getElementById('course-group-c1').style.display = 'block';
              
              if(document.getElementById('grade-group-c2')) document.getElementById('grade-group-c2').style.display = 'block';
              if(document.getElementById('board-group-c2')) document.getElementById('board-group-c2').style.display = 'block';
              
              categoryGroups.forEach(g => g.style.display = 'block');
              
              const grade1Radio = document.querySelector('input[name="grade_c1"]');
              if(grade1Radio) grade1Radio.setAttribute('required', 'true');
              
              const board1 = document.querySelector('[name="board_c1"]');
              if(board1) board1.setAttribute('required', 'true');
              
              updateCourses('c1');
              updateCourses('c2');
              
              document.getElementById('booking_type').value = 'consultation';
              document.querySelector('.lead-modal-header h3').innerText = 'Book a free consultation';
              setConsultDateMin(48);
          }
      }

      applyDetectedTimezone();
      const dateEl = document.getElementById('consultDate');
      if (dateEl && dateEl.value) {
        fetchSlots(dateEl.value);
      }

      ensurePhoneInputs();
      
      leadModal.classList.add('open');
      document.body.classList.add('modal-open');
      document.documentElement.classList.add('modal-open');
      // Reset form scroll to top when opening (page lock handled by React scroll-lock watcher)
      var formEl = document.getElementById('leadForm');
      if (formEl) formEl.scrollTop = 0;
      leadModal.scrollTop = 0;
    };

    const closeLeadModal = () => {
      leadModal.classList.remove('open');
      document.body.classList.remove('modal-open');
      document.documentElement.classList.remove('modal-open');
    };

    if (leadModalClose) {
      leadModalClose.addEventListener('click', closeLeadModal);
    }
    // Expose for React header / course cards / delegated handlers
    window.openLeadModal = openLeadModal;
    window.closeLeadModal = closeLeadModal;
    if (leadModal) {
      // Prevent accidental dismissal when clicking outside the panel
      // leadModal.addEventListener('click', (e) => { if (e.target === leadModal) closeLeadModal(); });
    }

    if (child2Toggle && child2Section) {
      child2Toggle.addEventListener('change', (e) => {
        if(e.target.checked) {
          child2Section.style.display = 'block';
          document.querySelector('[name="name_c2"]')?.setAttribute('required', 'true');
          document.querySelector('[name="age_c2"]')?.setAttribute('required', 'true');
          document.querySelector('[name="board_c2"]')?.setAttribute('required', 'true');
        } else {
          child2Section.style.display = 'none';
          document.querySelector('[name="name_c2"]')?.removeAttribute('required');
          document.querySelector('[name="age_c2"]')?.removeAttribute('required');
          document.querySelector('[name="board_c2"]')?.removeAttribute('required');
        }
      });
    }

    const consultDate = document.getElementById('consultDate');
    const consultTime = document.getElementById('consultTime');
    const consultCustomWrap = document.getElementById('consultCustomWrap');
    const consultCustomTime = document.getElementById('consultCustomTime');
    const consultCustomSlot = document.getElementById('consultCustomSlot');

    const tzAliases = {
      'Asia/Calcutta': 'Asia/Kolkata',
      'Asia/Katmandu': 'Asia/Kathmandu',
      'Asia/Saigon': 'Asia/Ho_Chi_Minh',
      'Asia/Rangoon': 'Asia/Yangon',
      'Asia/Macao': 'Asia/Macau',
      'America/Buenos_Aires': 'America/Argentina/Buenos_Aires',
    };

    const normalizeTimezone = (tz) => tzAliases[tz] || tz || 'UTC';

    const detectedTimezone = () => {
      const tzInput = document.getElementById('parentTimezoneInput');
      return normalizeTimezone((tzInput && tzInput.value) || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
    };

    const applyDetectedTimezone = () => {
      const tzInput = document.getElementById('parentTimezoneInput');
      if (!tzInput || tzInput.dataset.userModified) return;
      const browserTz = normalizeTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone);
      let exists = false;
      for (let i = 0; i < tzInput.options.length; i++) {
        if (tzInput.options[i].value === browserTz) {
          exists = true;
          break;
        }
      }
      if (!exists && browserTz) {
        tzInput.add(new Option(browserTz.replace(/_/g, ' '), browserTz, true, true));
      } else if (browserTz) {
        tzInput.value = browserTz;
      }
    };

    const formatSlotLabel = (utcIso, tz) => {
      const start = new Date(utcIso);
      if (Number.isNaN(start.getTime())) return '';
      const end = new Date(start.getTime() + 60 * 60000);
      const fmt = { hour: 'numeric', minute: '2-digit', timeZone: tz };
      try {
        return new Intl.DateTimeFormat(undefined, fmt).format(start) + ' – ' + new Intl.DateTimeFormat(undefined, fmt).format(end);
      } catch (e) {
        return start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) + ' – ' + end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
      }
    };

    const setConsultDateMin = (hoursAhead = 0) => {
      if (!consultDate) return;
      const d = new Date(Date.now() + Math.max(0, hoursAhead) * 3600 * 1000);
      const offset = d.getTimezoneOffset() * 60000;
      const localISODate = (new Date(d.getTime() - offset)).toISOString().split('T')[0];
      consultDate.setAttribute('min', localISODate);
      if (consultDate.value && consultDate.value < localISODate) {
        consultDate.value = '';
      }
    };

    setConsultDateMin(48);

    const toggleCustomTime = () => {
      const isCustom = consultTime && consultTime.value === 'custom';
      if (consultCustomWrap) consultCustomWrap.style.display = isCustom ? 'block' : 'none';
      if (consultCustomSlot) consultCustomSlot.value = isCustom ? '1' : '0';
      if (consultCustomTime) consultCustomTime.required = !!isCustom;
    };

    const fetchSlots = async (dateVal) => {
      const cTime = document.getElementById('consultTime');
      if (!cTime) return;
      
      if (!dateVal) {
        cTime.innerHTML = '<option value="" disabled selected>Select a date first</option>';
        toggleCustomTime();
        return;
      }

      const tz = detectedTimezone();
      const bookingType = document.getElementById('booking_type')?.value || 'consultation';
      cTime.innerHTML = '<option value="" disabled selected>Loading slots...</option>';

      try {
        const res = await fetch(`/api/consultation-slots?date=${dateVal}&timezone=${encodeURIComponent(tz)}&booking_type=${encodeURIComponent(bookingType)}`);
        if (!res.ok) throw new Error('Network error: ' + res.status);
        const data = await res.json();
        
        cTime.innerHTML = '<option value="" disabled selected>Select a recommended time</option>';
        
        if (data.slots && data.slots.length > 0) {
          data.slots.forEach(slot => {
            const opt = document.createElement('option');
            opt.value = slot.utc_iso || slot.time;
            const localLabel = slot.utc_iso ? formatSlotLabel(slot.utc_iso, tz) : slot.label;
            let suffix = '';
            if (!slot.available) {
              if (slot.too_soon) suffix = ' (Available after 48 hours)';
              else if (slot.past) suffix = ' (Past)';
              else suffix = ' (Booked)';
            }
            opt.textContent = localLabel + suffix;
            if (!slot.available) {
              opt.disabled = true;
              opt.style.color = '#999';
            }
            cTime.appendChild(opt);
          });
        }

        const customOpt = document.createElement('option');
        customOpt.value = 'custom';
        customOpt.textContent = 'Choose a different time';
        cTime.appendChild(customOpt);
        cTime.disabled = false;
        toggleCustomTime();
      } catch (err) {
        console.error("Slot fetch error:", err);
        cTime.innerHTML = `<option value="" disabled selected>Error: ${err.message}</option>`;
        const customOpt = document.createElement('option');
        customOpt.value = 'custom';
        customOpt.textContent = 'Choose a different time';
        cTime.appendChild(customOpt);
      }
    };

    document.addEventListener('change', (e) => {
      if (e.target && e.target.id === 'consultDate') {
        fetchSlots(e.target.value);
      }
      if (e.target && e.target.id === 'parentTimezoneInput') {
        e.target.dataset.userModified = '1';
        const dateVal = document.getElementById('consultDate')?.value;
        if (dateVal) fetchSlots(dateVal);
      }
      if (e.target && e.target.id === 'consultTime') {
        toggleCustomTime();
      }
    });
    document.addEventListener('input', (e) => {
      if (e.target && e.target.id === 'consultDate') {
        fetchSlots(e.target.value);
      }
    });
</script>

<div id="enrollModal" class="lead-modal-overlay">
      <div class="lead-modal-panel">
        <button id="enrollModalClose" class="mclose" aria-label="Close" onclick="document.getElementById('enrollModal').classList.remove('open')"><span class="material-symbols-outlined">close</span></button>
        <div class="lead-modal-header">
          <h3>Enroll Now</h3>
          <p>Select your course and the number of classes you would like to purchase.</p>
        </div>

        @if ($errors->any())
          <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
              <ul style="margin:0; padding-left:20px;">
                  @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
        @endif
        @php
            $enrollCategories = \App\Models\Category::with(['courses' => function($q) {
                $q->where('is_active', true);
            }])->where('is_active', true)->whereHas('courses', function($q) {
                $q->where('is_active', true);
            })->get(['id', 'name', 'slug']);
        @endphp
        <form id="enrollForm" action="{{ route('enroll.submit') }}" method="POST" autocomplete="on">
          @csrf
          
          <div class="form-group">
            <label>Select Category</label>
            <select id="enroll_category" class="form-control" onchange="updateEnrollCourses()">
              <option value="" disabled selected hidden>Select a category</option>
              @foreach($enrollCategories as $category)
                <option value="{{ $category->id }}" data-courses="{{ json_encode($category->courses->map(function($c) { return ['id' => $c->id, 'title' => $c->title]; })) }}">{{ $category->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label>Select Course</label>
            <select name="course_id" id="enroll_course" class="form-control" required>
              <option value="" disabled selected hidden>Please select a category first</option>
            </select>
          </div>

          <div class="form-group">
            <label>Number of Classes</label>
            <input type="number" name="classes" id="enroll_classes" class="form-control" min="1" max="100" value="24" required>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="font-size: 16px; padding: 14px; margin-top: 16px;">Proceed to Checkout</button>
        </form>
      </div>
    </div>
<style>
    .lead-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      padding: 16px;
    }
    .lead-modal-overlay.open {
      opacity: 1;
      pointer-events: auto;
    }
    .lead-modal-panel {
      background: #fff;
      border-radius: 20px;
      padding: 32px;
      max-width: 500px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      transform: translateY(20px) scale(0.98);
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .lead-modal-overlay.open .lead-modal-panel {
      transform: translateY(0) scale(1);
    }
    .lead-modal-panel .mclose {
      position: absolute;
      top: 16px;
      right: 16px;
      background: rgba(0,0,0,0.05);
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      transition: all 0.2s;
    }
    .lead-modal-panel .mclose:hover {
      background: rgba(0,0,0,0.1);
      color: #1e293b;
    }
    .lead-modal-header h3 {
      font-size: 24px;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 8px;
    }
    .lead-modal-header p {
      color: #64748b;
      font-size: 15px;
      line-height: 1.5;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      font-weight: 700;
      color: #334155;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.2s;
      background: #f8fafc;
      color: #0f172a;
    }
    .form-control:focus {
      outline: none;
      border-color: #3b82f6;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
    }
    .btn-block {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
</style>
<script>
    function openEnrollModal(event) {
        if(event) event.preventDefault();
        document.getElementById('enrollModal').classList.add('open');
    }
    
    function updateEnrollCourses() {
        const catSelect = document.getElementById('enroll_category');
        const courseSelect = document.getElementById('enroll_course');
        const selectedOpt = catSelect.options[catSelect.selectedIndex];
        
        courseSelect.innerHTML = '<option value="" disabled selected hidden>Select a course</option>';
        
        if (selectedOpt && selectedOpt.dataset.courses) {
            const courses = JSON.parse(selectedOpt.dataset.courses);
            courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.title;
                courseSelect.appendChild(opt);
            });
        }
    }
</script>

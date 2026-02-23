<?= $this->extend('theme/template') ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Teachers</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Teachers</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">List of Teachers</h3>
              <div class="float-right">
                <button type="button" class="btn btn-md btn-primary" data-toggle="modal" data-target="#AddTeacherModal">
                  <i class="fa fa-plus-circle fa fw"></i> Add New
                </button>
              </div>
            </div>
            <div class="card-body">
               <table id="teacherTable" class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th style="display:none;">id</th>
                    <th>Name</th>
                    <th>Birthday</th>
                    <th>Address</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="AddTeacherModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form id="addTeacherForm">
          <?= csrf_field() ?>
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-plus-circle fa fw"></i> Add New Teacher</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required />
              </div>
              <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="birthday" class="form-control" required />
              </div>
              <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3" required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class='fas fa-times-circle'></i> Cancel</button>
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="modal fade" id="editTeacherModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="far fa-edit fa fw"></i> Edit Teacher</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="editTeacherForm">
             <?= csrf_field() ?>
            <div class="modal-body">
              <input type="hidden" id="edit_id" name="id">
              <div class="form-group">
                  <label>Name</label>
                  <input type="text" name="name" id="edit_name" class="form-control" required />
              </div>
              <div class="form-group">
                <label>Birthday</label>
                <input type="date" class="form-control" id="edit_birthday" name="birthday" required>
              </div>
              <div class="form-group">
                <label>Address</label>
                <textarea class="form-control" id="edit_address" name="address" rows="3" required></textarea>
              </div>        
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class='fas fa-times-circle'></i> Cancel</button>
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<div class="toasts-top-right fixed" style="position: fixed; top: 1rem; right: 1rem; z-index: 9999;"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    const baseUrl = "<?= base_url() ?>";

    let table = $('#teacherTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + 'teacher/fetchRecords',
            type: 'POST',
            data: function (d) {
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>'; 
            }
        },
        columns: [
            { data: 'row_number', orderable: false },
            { data: 'id', visible: false },
            { data: 'name' },
            { data: 'birthday' },
            { data: 'address' },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">
                            <i class="fas fa-edit" style="color:white;"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>`;
                },
                orderable: false
            }
        ]
    });

    // ==========================================
    // OFFLINE & SYNC LOGIC FOR ADDING TEACHERS
    // ==========================================
    
    function saveTeacherOffline(formData) {
        let offlineTeachers = JSON.parse(localStorage.getItem('offlineTeachers')) || [];
        offlineTeachers.push(formData);
        localStorage.setItem('offlineTeachers', JSON.stringify(offlineTeachers));
        
        toastr.info('You are offline. Teacher saved locally and will sync when online.');
        
        $('#AddTeacherModal').modal('hide');
        $('#addTeacherForm')[0].reset();
        table.draw(false); 
    }

    function syncOfflineTeachers() {
        let offlineTeachers = JSON.parse(localStorage.getItem('offlineTeachers')) || [];
        
        if (offlineTeachers.length > 0) {
            toastr.info('Network restored. Syncing ' + offlineTeachers.length + ' offline teacher(s)...');
            
            let remainingTeachers = [];

            // Process each saved teacher
            offlineTeachers.forEach(function(formData) {
                $.ajax({
                    url: baseUrl + 'teacher/save',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.status === 'success') {
                            toastr.success('Offline teacher synced successfully!');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error('Failed to sync a teacher: ' + response.message);
                        }
                    },
                    error: function() {
                        // Keep in queue if network fails again during sync
                        remainingTeachers.push(formData);
                    }
                });
            });

            // Update localStorage (clears successful ones, keeps failed ones)
            localStorage.setItem('offlineTeachers', JSON.stringify(remainingTeachers));
        }
    }

    // Listeners for network status
    window.addEventListener('online', syncOfflineTeachers);

    if (navigator.onLine) {
        syncOfflineTeachers();
    }

    // Add Teacher Submit Event
    $('#addTeacherForm').submit(function (e) {
        e.preventDefault();
        
        let formData = $(this).serialize();

        if (!navigator.onLine) {
            // Offline path
            saveTeacherOffline(formData);
        } else {
            // Online path
            $.ajax({
                url: baseUrl + 'teacher/save',
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === 'success') {
                        $('#AddTeacherModal').modal('hide');
                        $('#addTeacherForm')[0].reset();
                        table.ajax.reload();
                        toastr.success('Teacher added successfully!');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    // Fallback to offline save if request drops mid-submission
                    saveTeacherOffline(formData);
                }
            });
        }
    });

    // ==========================================
    // EXISTING EDIT & DELETE LOGIC
    // ==========================================

    // Fetch Data for Edit
    $('#teacherTable').on('click', '.edit-btn', function () {
        let id = $(this).data('id');
        $.get(baseUrl + 'teacher/edit/' + id, function (response) {
            $('#edit_id').val(response.data.id);
            $('#edit_name').val(response.data.name);
            $('#edit_birthday').val(response.data.birthday);
            $('#edit_address').val(response.data.address);
            $('#editTeacherModal').modal('show');
        });
    });

    // Update Teacher
    $('#editTeacherForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: baseUrl + 'teacher/update',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    $('#editTeacherModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete Teacher
    $('#teacherTable').on('click', '.delete-btn', function () {
        let id = $(this).data('id');
        if (confirm('Are you sure you want to delete this teacher?')) {
            $.ajax({
                url: baseUrl + 'teacher/delete/' + id,
                type: 'DELETE',
                data: {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                },
                success: function (response) {
                    if (response.success) {
                        table.ajax.reload();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });
});
</script>
<?= $this->endSection() ?>
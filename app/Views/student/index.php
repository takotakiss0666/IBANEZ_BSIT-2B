<?= $this->extend('theme/template') ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Students</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Students</li>
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
              <h3 class="card-title">List of Students</h3>
              <div class="float-right">
                <button type="button" class="btn btn-md btn-primary" data-toggle="modal" data-target="#AddStudentModal">
                  <i class="fa fa-plus-circle fa fw"></i> Add New
                </button>
              </div>
            </div>
            <div class="card-body">
               <table id="studentTable" class="table table-bordered table-striped table-sm">
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

    <div class="modal fade" id="AddStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form id="addStudentForm">
          <?= csrf_field() ?>
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-plus-circle fa fw"></i> Add New Student</h5>
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

    <div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="far fa-edit fa fw"></i> Edit Student</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="editStudentForm">
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

    let table = $('#studentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + 'student/fetchRecords',
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
    // OFFLINE & SYNC LOGIC FOR ADDING STUDENTS
    // ==========================================
    
    function saveStudentOffline(formData) {
        let offlineStudents = JSON.parse(localStorage.getItem('offlineStudents')) || [];
        offlineStudents.push(formData);
        localStorage.setItem('offlineStudents', JSON.stringify(offlineStudents));
        
        toastr.info('You are offline. Student saved locally and will sync when online.');
        
        $('#AddStudentModal').modal('hide');
        $('#addStudentForm')[0].reset();
        table.draw(false); 
    }

    function syncOfflineStudents() {
        let offlineStudents = JSON.parse(localStorage.getItem('offlineStudents')) || [];
        
        if (offlineStudents.length > 0) {
            toastr.info('Network restored. Syncing ' + offlineStudents.length + ' offline student(s)...');
            
            let remainingStudents = [];

            // Process each saved student
            offlineStudents.forEach(function(formData) {
                $.ajax({
                    url: baseUrl + 'student/save',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.status === 'success') {
                            toastr.success('Offline student synced successfully!');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error('Failed to sync a student: ' + response.message);
                        }
                    },
                    error: function() {
                        // Keep in queue if network fails again during sync
                        remainingStudents.push(formData);
                    }
                });
            });

            // Update localStorage (clears successful ones, keeps failed ones)
            localStorage.setItem('offlineStudents', JSON.stringify(remainingStudents));
        }
    }

    // Listeners for network status
    window.addEventListener('online', syncOfflineStudents);

    if (navigator.onLine) {
        syncOfflineStudents();
    }

    // Add Student Submit Event
    $('#addStudentForm').submit(function (e) {
        e.preventDefault();
        
        let formData = $(this).serialize();

        if (!navigator.onLine) {
            // Offline path
            saveStudentOffline(formData);
        } else {
            // Online path
            $.ajax({
                url: baseUrl + 'student/save',
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === 'success') {
                        $('#AddStudentModal').modal('hide');
                        $('#addStudentForm')[0].reset();
                        table.ajax.reload();
                        toastr.success('Student added successfully!');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    // Fallback to offline save if request drops mid-submission
                    saveStudentOffline(formData);
                }
            });
        }
    });

    // ==========================================
    // EXISTING EDIT & DELETE LOGIC
    // ==========================================

    // Fetch Data for Edit
    $('#studentTable').on('click', '.edit-btn', function () {
        let id = $(this).data('id');
        $.get(baseUrl + 'student/edit/' + id, function (response) {
            $('#edit_id').val(response.data.id);
            $('#edit_name').val(response.data.name);
            $('#edit_birthday').val(response.data.birthday);
            $('#edit_address').val(response.data.address);
            $('#editStudentModal').modal('show');
        });
    });

    // Update Student
    $('#editStudentForm').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: baseUrl + 'student/update',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    $('#editStudentModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete Student
    $('#studentTable').on('click', '.delete-btn', function () {
        let id = $(this).data('id');
        if (confirm('Are you sure you want to delete this student?')) {
            $.ajax({
                url: baseUrl + 'student/delete/' + id,
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
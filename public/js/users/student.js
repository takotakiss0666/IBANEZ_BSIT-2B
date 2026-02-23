function showToast(type, message) {
    if (type === 'success') {
        toastr.success(message, 'Success');
    } else {
        toastr.error(message, 'Error');
    }
}

// Add New Student
$('#addStudentForm').on('submit', function (e) {
    e.preventDefault();
    $.ajax({
        url: baseUrl + 'student/save',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                $('#AddStudentModal').modal('hide');
                $('#addStudentForm')[0].reset();
                showToast('success', 'Student added successfully!');
                setTimeout(() => {
                    location.reload();
                }, 1000); 
            } else {
                showToast('error', response.message || 'Failed to add student.');
            }
        },
        error: function () {
            showToast('error', 'An error occurred.');
        }
    });
});

// Fetch Student Data for Edit Modal
$(document).on('click', '.edit-btn', function () {
   const studentId = $(this).data('id'); 
   $.ajax({
        url: baseUrl + 'student/edit/' + studentId,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.data) {
                $('#editStudentModal #edit_id').val(response.data.id);
                $('#editStudentModal #edit_name').val(response.data.name);
                $('#editStudentModal #edit_birthday').val(response.data.birthday);
                $('#editStudentModal #edit_address').val(response.data.address);
                $('#editStudentModal').modal('show');
            } else {
                alert('Error fetching student data');
            }
        },
        error: function () {
            alert('Error fetching student data');
        }
    });
});

// Update Student
$(document).ready(function () {
    $('#editStudentForm').on('submit', function (e) {
        e.preventDefault(); 

        $.ajax({
            url: baseUrl + 'student/update',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#editStudentModal').modal('hide');
                    showToast('success', 'Student Updated successfully!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error updating: ' + (response.message || 'Unknown error'));
                }
            },
            error: function (xhr) {
                alert('Error updating');
                console.error(xhr.responseText);
            }
        });
    });
});

// Delete Student
$(document).on('click', '.delete-btn', function () {
    const studentId = $(this).data('id');
    const csrfName = $('meta[name="csrf-name"]').attr('content');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    if (confirm('Are you sure you want to delete this student?')) {
        $.ajax({
            url: baseUrl + 'student/delete/' + studentId,
            method: 'POST', 
            data: {
                _method: 'DELETE',
                [csrfName]: csrfToken
            },
            success: function (response) {
                if (response.success) {
                    showToast('success', 'Student deleted successfully.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(response.message || 'Failed to delete.');
                }
            },
            error: function () {
                alert('Something went wrong while deleting.');
            }
        });
    }
});

// Initialize DataTable
$(document).ready(function () {
    const $table = $('#studentTable');

    const csrfName = 'csrf_test_name'; 
    const csrfToken = $('input[name="' + csrfName + '"]').val();

    $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: baseUrl + 'student/fetchRecords',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        },
        columns: [
        { data: 'row_number' },
        { data: 'id', visible: false },
        { data: 'name' },
        { data: 'birthday' },
        { data: 'address' },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                return `
                <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">
                <i class="far fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                <i class="fas fa-trash-alt"></i>
                </button>
                `;
            }
        }
        ],
        responsive: true,
        autoWidth: false
    });
});
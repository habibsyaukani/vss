

<?php $__env->startSection('title', 'User Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3><i class="fas fa-users"></i> User Management</h3>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?php echo e(route('admin.user.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create User
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover datatable" width="100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(function() {
    console.log('✅ User Management JavaScript loaded');
    console.log('DataTables available?', typeof $.fn.DataTable !== 'undefined');
    console.log('AJAX URL:', "<?php echo e(route('admin.user.data')); ?>");
    
    $('.datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "<?php echo e(route('admin.user.data')); ?>",
        columns: [
            {data: 'name'},
            {data: 'username'},
            {data: 'role_badge', orderable: false, searchable: false},
            {data: 'status_badge', orderable: false, searchable: false},
            {data: 'created_at_formatted'},
            {data: 'actions', orderable: false, searchable: false}
        ],
        error: function(xhr, error, thrown) {
            console.error('❌ DataTables error:', {xhr, error, thrown});
            alert('Error loading data: ' + error);
        }
    });

    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure?')) {
            $.ajax({
                type: 'DELETE',
                url: '/admin/user/' + id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    $('.datatable').DataTable().ajax.reload();
                    alert('User deleted successfully!');
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    alert('Error deleting user!');
                }
            });
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views/admin/user/index.blade.php ENDPATH**/ ?>
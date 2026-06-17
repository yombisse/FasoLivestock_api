{{-- resources/views/admin/layouts/flash.blade.php --}}

@if(session('success') || session('error') || session('warning'))
<div id="flash-messages">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2"
             x-data x-init="setTimeout(() => $el.remove(), 4000)">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2"
             x-data x-init="setTimeout(() => $el.remove(), 5000)">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2"
             x-data x-init="setTimeout(() => $el.remove(), 5000)">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>
@endif
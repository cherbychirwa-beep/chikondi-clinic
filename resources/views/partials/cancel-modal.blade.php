{{-- Shared cancellation dialog. Buttons carry data-action (the cancel URL) and data-label. --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="#" id="cancelForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="cancelLabel"></p>
                <label for="cancel_reason" class="form-label">Cancellation reason <span class="text-danger">*</span></label>
                <textarea name="reason" id="cancel_reason" class="form-control" rows="3" minlength="5" maxlength="500" required></textarea>
                <div class="form-text">The slot becomes available for booking again once cancelled.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep appointment</button>
                <button type="submit" class="btn btn-danger">Cancel appointment</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('cancelModal')?.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        document.getElementById('cancelForm').action = trigger.dataset.action;
        document.getElementById('cancelLabel').textContent = trigger.dataset.label || '';
        document.getElementById('cancel_reason').value = '';
    });
</script>
@endpush

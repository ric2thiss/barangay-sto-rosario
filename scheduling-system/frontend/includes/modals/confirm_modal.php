<?php
// frontend/includes/modals/confirm_modal.php
?>

<div class="modal fade" id="bpssConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title" id="bpssConfirmTitle">Confirm</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="bpssConfirmMessage" class="alert alert-warning mb-0">
          Are you sure?
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="bpssConfirmCancel">
          Cancel
        </button>

        <button type="button" class="btn btn-danger" id="bpssConfirmOk">
          Confirm
        </button>
      </div>

    </div>
  </div>
</div>
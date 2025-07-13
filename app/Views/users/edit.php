<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card card-body">
                <h2>Edit Profile</h2>
                <form action="<?php echo URLROOT; ?>/users/edit" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username: <sup>*</sup></label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['username']; ?>">
                        <span class="invalid-feedback"><?php echo $data['username_err']; ?></span>
                    </div>
                    <div class="mb-3">
                        <label for="fname" class="form-label">First Name: <sup>*</sup></label>
                        <input type="text" name="fname" class="form-control <?php echo (!empty($data['fname_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['fname']; ?>">
                        <span class="invalid-feedback"><?php echo $data['fname_err']; ?></span>
                    </div>
                    <div class="mb-3">
                        <label for="lname" class="form-label">Last Name: <sup>*</sup></label>
                        <input type="text" name="lname" class="form-control <?php echo (!empty($data['lname_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['lname']; ?>">
                        <span class="invalid-feedback"><?php echo $data['lname_err']; ?></span>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email: <sup>*</sup></label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>">
                        <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                    </div>
                    <div class="d-grid my-3">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProfileModal">
                            Delete Profile
                        </button>
                    </div>
                    <div class="row mt-4">
                        <div class="col">
                            <input type="submit" value="Save Changes" class="btn btn-success w-100">
                        </div>
                        <div class="col">
                            <a href="<?php echo URLROOT; ?>/users/profile" class="btn btn-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Profile Modal -->
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProfileModalLabel">Are you sure you want to delete your profile?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This action cannot be undone. Please review what will happen before proceeding.</p>
                
                <strong>What will be permanently deleted:</strong>
                <ul>
                    <li>Your user profile and login credentials.</li>
                    <li>Your membership in all accounts you are a part of.</li>
                    <li>Your association with any receipt items.</li>
                    <li>Any pending account invitations you have sent.</li>
                </ul>

                <strong>What will be kept:</strong>
                <ul>
                    <li>Accounts you created will remain, but will no longer show you as the creator.</li>
                    <li>Receipts you paid for will remain, but will no longer show you as the payer.</li>
                </ul>
            </div>
            <div class="modal-footer justify-content-between">
                <form action="<?php echo URLROOT; ?>/users/delete" method="post" class="m-0">
                    <input type="submit" value="Confirm Deletion" class="btn btn-danger">
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Profile Modal -->
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProfileModalLabel">Are you sure you want to delete your profile?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This action cannot be undone. Please review what will happen before proceeding.</p>
                
                <strong>What will be permanently deleted:</strong>
                <ul>
                    <li>Your user profile and login credentials.</li>
                    <li>Your membership in all accounts you are a part of.</li>
                    <li>Your association with any receipt items.</li>
                    <li>Any pending account invitations you have sent.</li>
                </ul>

                <strong>What will be kept:</strong>
                <ul>
                    <li>Accounts you created will remain, but will no longer show you as the creator.</li>
                    <li>Receipts you paid for will remain, but will no longer show you as the payer.</li>
                </ul>
            </div>
            <div class="modal-footer justify-content-between">
                <form action="<?php echo URLROOT; ?>/users/delete" method="post" class="m-0">
                    <input type="submit" value="Confirm Deletion" class="btn btn-danger">
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>

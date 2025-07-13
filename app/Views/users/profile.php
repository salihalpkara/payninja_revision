<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card card-body">
                <h2>User Profile</h2>
                <p><strong>Username:</strong> <?php echo $data['user']->username; ?></p>
                <p><strong>First Name:</strong> <?php echo $data['user']->fname; ?></p>
                <p><strong>Last Name:</strong> <?php echo $data['user']->lname; ?></p>
                <p><strong>Email:</strong> <?php echo $data['user']->email; ?></p>
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteProfileModal">
                        Delete Profile
                    </button>
                    <a href="<?php echo URLROOT; ?>/users/edit" class="btn btn-primary">Edit Profile</a>
                </div>
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
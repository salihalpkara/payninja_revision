<?php
class Accounts extends Controller {
    public function __construct(){
        if(!isLoggedIn()){
            redirect('users/login');
        }

        $this->accountModel = $this->model('Account');
        $this->receiptModel = $this->model('Receipt'); // Add Receipt model
        $this->userModel = $this->model('User'); // Need User model to search for users
    }

    public function index(){
        // Get accounts with their users
        $accounts = $this->accountModel->getAccountsWithUsersByUserId($_SESSION['user_id']);

        $data = [
            'accounts' => $accounts
        ];

        $this->view('accounts/index', $data);
    }

    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Sanitize POST array
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'account_name' => trim($_POST['account_name']),
                'currency' => trim($_POST['currency']),
                'user_id' => $_SESSION['user_id'],
                'account_name_err' => '',
                'currency_err' => ''
            ];

            // Validate data
            if(empty($data['account_name'])){
                $data['account_name_err'] = 'Please enter account name';
            } elseif (strlen($data['account_name']) > 50) {
                $data['account_name_err'] = 'Account name cannot be more than 50 characters';
            }
            
            if(empty($data['currency'])){
                $data['currency_err'] = 'Please enter currency';
            }

            // Make sure no errors
            if(empty($data['account_name_err']) && empty($data['currency_err'])){
                // Validated
                if($this->accountModel->addAccount($data)){
                    flash('account_message', 'Account Added');
                    redirect('accounts');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('accounts/add', $data);
            }

        } else {
            $data = [
                'account_name' => '',
                'currency' => ''
            ];

            $this->view('accounts/add', $data);
        }
    }

    public function show($id){
        // Check if user is authorized to view this account
        if(!$this->accountModel->isUserInAccount($id, $_SESSION['user_id'])){
            flash('account_message', 'You are not authorized to view this account.', 'alert-danger');
            redirect('accounts');
        }

        $accountInfo = $this->accountModel->getAccountById($id);
        $accountUsersList = $this->accountModel->getUsersInAccount($id);
        $accountUserIds = array_column($accountUsersList, 'id');
        $accountUsersMap = [];
        foreach ($accountUsersList as $user) {
            $accountUsersMap[$user->id] = $user;
        }

        // Load array_helpers for findSubsetsOfIds
        require_once APPROOT . '/app/helpers/array_helpers.php';
        $beneficiarySubsets = findSubsetsOfIds($accountUserIds);

        $latestReceipts = $this->receiptModel->getLatestReceiptsByAccountId($id, 3);
        $statistics = $this->receiptModel->getAccountStatistics($id, $beneficiarySubsets);
        $paidForOthersStats = $this->receiptModel->getPaidForOthersStatistics($id);

        $data = [
            'accountInfo' => $accountInfo,
            'accountUsersList' => $accountUsersList,
            'accountUsersMap' => $accountUsersMap,
            'beneficiarySubsets' => $beneficiarySubsets,
            'latestReceipts' => $latestReceipts,
            'statistics' => $statistics,
            'paidForOthersStats' => $paidForOthersStats,
            'breadcrumbs' => [
                ['url' => URLROOT, 'label' => 'Home'],
                ['url' => URLROOT . '/accounts', 'label' => 'Accounts'],
                ['label' => $accountInfo->account_name]
            ]
        ];

        $this->view('accounts/show', $data);
    }

    public function updateAccountInfo(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Get raw POST data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $account_id = $data['account_id'];
            $new_name = trim($data['new_name']);
            $new_currency = strtoupper(trim($data['new_currency']));

            // Validate input
            if (empty($new_name) || strlen($new_name) > 50 || empty($new_currency)) {
                echo json_encode(['success' => false, 'message' => 'Account name must be between 1 and 50 characters, and currency cannot be empty.']);
                return;
            }

            // Check if user is authorized to update this account
            if(!$this->accountModel->isUserInAccount($account_id, $_SESSION['user_id'])){
                echo json_encode(['success' => false, 'message' => 'You are not authorized to update this account.']);
                return;
            }

            if($this->accountModel->updateAccount($account_id, $new_name, $new_currency)){
                echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update account.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }

    public function deleteAccount(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $account_id = $data['account_id'];

            // Check if user is authorized to delete this account (only the creator can delete)
            $account = $this->accountModel->getAccountById($account_id);
            if(!$account || $account->created_by != $_SESSION['user_id']){
                echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this account.']);
                return;
            }

            if($this->accountModel->deleteAccount($account_id)){
                echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete account.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }

    public function removeUserFromAccount(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $account_id = $data['account_id'];
            $user_id_to_remove = $data['user_id'];

            // Authorization: Only the account creator can remove users
            $account = $this->accountModel->getAccountById($account_id);
            if(!$account || $account->created_by != $_SESSION['user_id']){
                echo json_encode(['success' => false, 'message' => 'You are not authorized to remove users from this account.']);
                return;
            }

            // Prevent creator from removing themselves
            if($user_id_to_remove == $_SESSION['user_id']){
                echo json_encode(['success' => false, 'message' => 'You cannot remove yourself as the account creator.']);
                return;
            }

            // Check for financial history before removing the user
            if ($this->accountModel->userHasFinancialHistory($account_id, $user_id_to_remove)) {
                echo json_encode(['success' => false, 'message' => 'This user cannot be removed because they are part of one or more receipts. To remove them, you must first delete the associated receipts.']);
                return;
            }

            if($this->accountModel->removeUserFromAccount($account_id, $user_id_to_remove)){
                echo json_encode(['success' => true, 'message' => 'User removed successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove user.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }

    public function addUserToAccount(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $account_id = $data['account_id'];
            $user_id_to_add = $data['user_id'];

            // Authorization: Only the account creator can add users
            $account = $this->accountModel->getAccountById($account_id);
            if(!$account || $account->created_by != $_SESSION['user_id']){
                echo json_encode(['success' => false, 'message' => 'You are not authorized to add users to this account.']);
                return;
            }

            // Find user by ID
            $user_to_add = $this->userModel->findUserById($user_id_to_add);
            if(!$user_to_add){
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                return;
            }

            // Check if user is already in the account
            if($this->accountModel->isUserInAccount($account_id, $user_to_add->id)){
                echo json_encode(['success' => false, 'message' => 'User is already a member of this account.']);
                return;
            }

            if($this->accountModel->addUserToAccount($account_id, $user_to_add->id)){
                echo json_encode(['success' => true, 'message' => 'User added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add user.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }

    public function searchUsers(){
        header('Content-Type: application/json');

        if(!isLoggedIn()){
            echo json_encode([]);
            return;
        }

        $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
        $accountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

        if (empty($searchTerm) || $accountId <= 0) {
            echo json_encode([]);
            return;
        }

        // Authorization: Ensure the current user has access to the account
        if(!$this->accountModel->isUserInAccount($accountId, $_SESSION['user_id'])){
            echo json_encode([]); // Or return an error message if preferred
            return;
        }

        $users = $this->userModel->searchUsersNotInAccount($searchTerm, $accountId);
        echo json_encode($users);
    }

    public function invite($account_id){
        // Generate a unique token
        $token = bin2hex(random_bytes(16));

        // Set expiration date (e.g., 24 hours from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $data = [
            'account_id' => $account_id,
            'invited_by' => $_SESSION['user_id'],
            'token' => $token,
            'expires_at' => $expires_at
        ];

        if($this->accountModel->createInvitation($data)){
            // Show the invitation link to the user
            $invitation_link = URLROOT . '/accounts/join/' . $token;
            flash('account_message', 'Invitation link created: ' . $invitation_link);
            redirect('accounts/show/' . $account_id);
        } else {
            die('Something went wrong');
        }
    }

    public function join($token){
        if(!isLoggedIn()){
            // Store the token in the session and redirect to login
            $_SESSION['join_token'] = $token;
            redirect('users/login');
        }

        $invitation = $this->accountModel->getInvitationByToken($token);

        if($invitation){
            // Check if the invitation has expired
            if(strtotime($invitation->expires_at) > time()){
                // Check if the user is already in the account
                if(!$this->accountModel->isUserInAccount($invitation->account_id, $_SESSION['user_id'])){
                    // Add the user to the account
                    if($this->accountModel->addUserToAccount($invitation->account_id, $_SESSION['user_id'])){
                        // Mark the invitation as accepted
                        $this->accountModel->markInvitationAsAccepted($invitation->id);
                        flash('account_message', 'You have successfully joined the account.');
                        redirect('accounts/show/' . $invitation->account_id);
                    } else {
                        die('Something went wrong');
                    }
                } else {
                    flash('account_message', 'You are already a member of this account.', 'alert alert-danger');
                    redirect('accounts/show/' . $invitation->account_id);
                }
            } else {
                die('Invitation has expired');
            }
        } else {
            die('Invalid invitation token');
        }
    }
}
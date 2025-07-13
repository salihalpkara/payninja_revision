<?php
class Receipts extends Controller {
    public function __construct(){
        if(!isLoggedIn()){
            redirect('users/login');
        }

        $this->receiptModel = $this->model('Receipt');
        $this->accountModel = $this->model('Account');
        $this->userModel = $this->model('User');
    }

    public function index($account_id){
        // Get account
        $account = $this->accountModel->getAccountById($account_id);

        // Get receipts
        $receipts = $this->receiptModel->getReceiptsByAccountId($account_id);

        $data = [
            'account' => $account,
            'receipts' => $receipts
        ];

        $this->view('receipts/index', $data);
    }

    public function add($account_id){
        // This method handles both the display of the 'add receipt' form (GET)
        // and the processing of the submitted receipt data (POST).

        // Fetch common data needed for the view, regardless of request method.
        $accountDetails = $this->accountModel->getAccountById($account_id);
        if (!$accountDetails) {
            flash('receipt_message', 'Account not found.', 'alert-danger');
            redirect('accounts');
        }

        // Authorization check: Ensure the logged-in user is part of the account.
        if (!$this->accountModel->isUserInAccount($account_id, $_SESSION['user_id'])) {
            flash('account_message', 'You are not authorized to view this account.', 'alert-danger');
            redirect('accounts');
        }

        $locations = $this->receiptModel->getLocationsByAccountId($account_id);
        $usersInAccount = $this->accountModel->getUsersInAccount($account_id);
        $currencies = $this->receiptModel->getCurrenciesByAccountId($account_id, $accountDetails->currency);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Handle POST request for adding a new receipt.
            $json = file_get_contents('php://input');
            $postData = json_decode($json, true);

            // Prepare data structure for processing.
            $data = [
                'account_id' => $account_id,
                'payer_id' => isset($postData['payer_id']) ? trim($postData['payer_id']) : $_SESSION['user_id'],
                'location' => isset($postData['location']) ? trim($postData['location']) : '',
                'total_amount' => isset($postData['totalAmount']) ? trim($postData['totalAmount']) : '',
                'currency' => isset($postData['currency']) ? trim($postData['currency']) : $accountDetails->currency,
                'receipt_date_time' => isset($postData['receipt_date_time']) ? trim($postData['receipt_date_time']) : '',
                'receipt_note' => isset($postData['note']) ? trim($postData['note']) : '',
                'items' => isset($postData['items']) ? $postData['items'] : [],
                'location_err' => '',
                'total_amount_err' => '',
                'receipt_date_time_err' => '',
                'item_err' => ''
            ];

            // --- Data Validation ---
            if (empty($data['location'])) {
                $data['location_err'] = 'Please enter a location.';
            }
            if (empty($data['total_amount'])) {
                $data['total_amount_err'] = 'Please enter the total amount.';
            }
            if (empty($data['receipt_date_time'])) {
                $data['receipt_date_time_err'] = 'Please enter the receipt date and time.';
            }
            if (empty($data['items'])) {
                $data['item_err'] = 'Please add at least one item.';
            } else {
                foreach ($data['items'] as $item) {
                    if (empty(trim($item['name']))) {
                        $data['item_err'] = 'Item name cannot be empty.';
                        break;
                    }
                    if (empty(trim($item['price']))) {
                        $data['item_err'] = 'Item price cannot be empty.';
                        break;
                    }
                    if (empty($item['beneficiaries'])) {
                        $data['item_err'] = 'Each item must have at least one beneficiary.';
                        break;
                    }
                }
            }

            // If validation passes, proceed to add the receipt.
            if (empty($data['location_err']) && empty($data['total_amount_err']) && empty($data['receipt_date_time_err']) && empty($data['item_err'])) {
                if ($this->receiptModel->addReceiptWithItemsFromAPI($data)) {
                    // On successful creation, send a success response.
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Receipt Added Successfully',
                        'redirect_url' => URLROOT . '/accounts/show/' . $account_id
                    ]);
                    return; // Stop script execution
                } else {
                    // Handle potential database errors.
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Something went wrong while adding the receipt.']);
                    return; // Stop script execution
                }
            } else {
                // If validation fails, send back the errors.
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Validation failed',
                    'errors' => [
                        'location' => $data['location_err'],
                        'total_amount' => $data['total_amount_err'],
                        'date' => $data['receipt_date_time_err'],
                        'items' => $data['item_err']
                    ]
                ]);
                return; // Stop script execution
            }

        } else {
            // Handle GET request: Display the 'add receipt' form.
            $data = [
                'page_title' => $accountDetails->account_name . ' - New Receipt',
                'account_id' => $account_id,
                'location' => '',
                'total_amount' => '',
                'currency' => $accountDetails->currency,
                'receipt_date_time' => '',
                'receipt_note' => '',
                'location_err' => '',
                'total_amount_err' => '',
                'receipt_date_time_err' => '',
                'item_err' => '',
                'default_account_currency' => $accountDetails->currency,
                'locations' => $locations,
                'users_in_account' => $usersInAccount,
                'currencies' => $currencies
            ];

            $this->view('receipts/add', $data);
        }
    }

    public function edit($receipt_id){
        // Fetch receipt details
        $receipt = $this->receiptModel->getReceiptDetailsForEdit($receipt_id);
        if (!$receipt) {
            flash('receipt_message', 'Receipt not found.', 'alert-danger');
            redirect('accounts'); // Redirect to accounts if receipt not found
        }

        // Authorization: Check if user has access to the account this receipt belongs to
        if(!$this->accountModel->isUserInAccount($receipt->account_id, $_SESSION['user_id'])){
            flash('receipt_message', 'You are not authorized to edit this receipt.', 'alert-danger');
            redirect('accounts');
        }

        // Fetch common data for the form
        $accountDetails = $this->accountModel->getAccountById($receipt->account_id);
        $locations = $this->receiptModel->getLocationsByAccountId($receipt->account_id);
        $usersInAccount = $this->accountModel->getUsersInAccount($receipt->account_id);
        $currencies = $this->receiptModel->getCurrenciesByAccountId($receipt->account_id, $accountDetails->currency);

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            // Sanitize POST array
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'page_title' => 'Edit Receipt #' . $receipt_id,
                'receipt_id' => $receipt_id,
                'account_id' => $receipt->account_id,
                'payer_id' => trim($_POST['payer_id']),
                'location' => trim($_POST['location']),
                'total_amount' => trim($_POST['total_amount']),
                'currency' => strtoupper(trim($_POST['currency'])),
                'receipt_date_time' => trim($_POST['receipt_date_time']),
                'receipt_note' => trim($_POST['receipt_note']),
                'item_name' => isset($_POST['item_name']) ? $_POST['item_name'] : [],
                'item_price' => isset($_POST['item_price']) ? $_POST['item_price'] : [],
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : [],
                'bought_for' => isset($_POST['bought_for']) ? $_POST['bought_for'] : [],
                'deleted_items' => isset($_POST['deleted_items']) ? $_POST['deleted_items'] : [],
                'location_err' => '',
                'total_amount_err' => '',
                'receipt_date_time_err' => '',
                'item_err' => '',
                'default_account_currency' => $accountDetails->currency,
                'locations' => $locations,
                'users_in_account' => $usersInAccount,
                'currencies' => $currencies
            ];

            // Validate data (similar to add method)
            if(empty($data['location'])){
                $data['location_err'] = 'Please enter location';
            }
            if(empty($data['total_amount'])){
                $data['total_amount_err'] = 'Please enter total amount';
            }
            if(empty($data['receipt_date_time'])){
                $data['receipt_date_time_err'] = 'Please enter date and time';
            }

            // Validate items
            if (empty($data['item_name'])) {
                $data['item_err'] = 'Please add at least one item.';
            } else {
                foreach ($data['item_name'] as $index => $itemName) {
                    if (empty(trim($itemName))) {
                        $data['item_err'] = 'Item name cannot be empty.';
                        break;
                    }
                    if (empty(trim($data['item_price'][$index]))) {
                        $data['item_err'] = 'Item price cannot be empty.';
                        break;
                    }
                    if (empty(trim($data['amount'][$index]))) {
                        $data['item_err'] = 'Item quantity cannot be empty.';
                        break;
                    }
                    if (!isset($data['bought_for'][$index]) || empty($data['bought_for'][$index])) {
                        $data['item_err'] = 'Please select at least one person for each item.';
                        break;
                    }
                }
            }

            // Make sure no errors
            if(empty($data['location_err']) && empty($data['total_amount_err']) && empty($data['receipt_date_time_err']) && empty($data['item_err'])){
                // Validated
                if($this->receiptModel->updateReceiptWithItems($data)){
                    flash('receipt_message', 'Receipt Updated Successfully');
                    redirect('receipts/index/' . $data['account_id']);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $data['receipt'] = $receipt; // Pass original receipt data back to view
                $this->view('receipts/edit', $data);
            }

        } else {
            // Init data for GET request
            $data = [
                'page_title' => 'Edit Receipt #' . $receipt_id,
                'receipt_id' => $receipt_id,
                'account_id' => $receipt->account_id,
                'payer_id' => $receipt->payer_id, // Ensure payer_id is set for GET request
                'location' => $receipt->location,
                'total_amount' => $receipt->total_amount,
                'currency' => $receipt->currency,
                'receipt_date_time' => date('Y-m-d\TH:i', strtotime($receipt->receipt_date_time)),
                'receipt_note' => $receipt->receipt_note,
                'items' => $receipt->items,
                'location_err' => '',
                'total_amount_err' => '',
                'receipt_date_time_err' => '',
                'item_err' => '',
                'default_account_currency' => $accountDetails->currency,
                'locations' => $locations,
                'users_in_account' => $usersInAccount,
                'currencies' => $currencies
            ];

            $this->view('receipts/edit', $data);
        }
    }

    public function show($id){
        $receipt = $this->receiptModel->getReceiptById($id);
        $items = $this->receiptModel->getItemsByReceiptId($id);
        $notes = $this->receiptModel->getNotesByReceiptId($id);

        $data = [
            'receipt' => $receipt,
            'items' => $items,
            'notes' => $notes
        ];

        $this->view('receipts/show', $data);
    }

    public function delete(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $receipt_id = $data['receipt_id'];
            $account_id = $data['account_id'];

            // Authorization: Check if the user is authorized to delete this receipt for this account
            // This involves checking if the receipt belongs to the account, and if the user has access to that account.
            if(!$this->accountModel->isUserInAccount($account_id, $_SESSION['user_id'])){
                echo json_encode(['success' => false, 'message' => 'You are not authorized to delete receipts from this account.']);
                return;
            }

            // Further check: Does the receipt actually belong to the provided account_id?
            $receipt = $this->receiptModel->getReceiptById($receipt_id);
            if(!$receipt || $receipt->account_id != $account_id){
                echo json_encode(['success' => false, 'message' => 'Receipt not found in the specified account.']);
                return;
            }

            if($this->receiptModel->deleteReceipt($receipt_id)){
                echo json_encode(['success' => true, 'message' => 'Receipt deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete receipt.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
    }
}
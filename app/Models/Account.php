<?php
class Account extends Model {
    public function __construct(){
        parent::__construct();
    }

    public function getAccountsWithUsersByUserId($user_id){
        $this->db->query("
            SELECT 
                a.id as account_id,
                a.account_name,
                a.currency,
                a.created_at,
                creator_user.username as created_by_username,
                GROUP_CONCAT(au_user.id, ':', au_user.fname, ':', au_user.lname SEPARATOR ';') as account_users
            FROM 
                account a
            JOIN 
                account_user au ON a.id = au.account_id
            JOIN 
                app_user creator_user ON a.created_by = creator_user.id
            LEFT JOIN
                account_user au_join ON a.id = au_join.account_id
            LEFT JOIN
                app_user au_user ON au_join.user_id = au_user.id
            WHERE 
                au.user_id = :user_id
            GROUP BY
                a.id
            ORDER BY
                a.created_at DESC
        ");
        $this->db->bind(':user_id', $user_id);
        $results = $this->db->resultSet();

        // Process results to group users by account
        $accounts = [];
        foreach ($results as $row) {
            $account_id = $row->account_id;
            if (!isset($accounts[$account_id])) {
                $accounts[$account_id] = [
                    'id' => $row->account_id,
                    'account_name' => $row->account_name,
                    'currency' => $row->currency,
                    'created_at' => $row->created_at,
                    'created_by_username' => $row->created_by_username,
                    'users' => []
                ];
            }
            if (!empty($row->account_users)) {
                $user_data = explode(';', $row->account_users);
                foreach ($user_data as $user_str) {
                    list($user_id, $fname, $lname) = explode(':', $user_str);
                    $accounts[$account_id]['users'][] = [
                        'id' => $user_id,
                        'fname' => $fname,
                        'lname' => $lname
                    ];
                }
            }
        }
        return array_values($accounts);
    }

    public function getAccountsByUserId($user_id){
        $this->db->query('SELECT a.*, u.username FROM account a JOIN account_user au ON a.id = au.account_id JOIN app_user u ON a.created_by = u.id WHERE au.user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $results = $this->db->resultSet();
        return $results;
    }

    public function addAccount($data){
        $this->db->query('INSERT INTO account (account_name, currency, created_by) VALUES(:account_name, :currency, :created_by)');
        // Bind values
        $this->db->bind(':account_name', $data['account_name']);
        $this->db->bind(':currency', $data['currency']);
        $this->db->bind(':created_by', $data['user_id']);

        // Execute
        if($this->db->execute()){
            $account_id = $this->db->lastInsertId();
            return $this->addUserToAccount($account_id, $data['user_id']);
        } else {
            return false;
        }
    }

    public function addUserToAccount($account_id, $user_id){
        $this->db->query('INSERT INTO account_user (account_id, user_id) VALUES(:account_id, :user_id)');
        // Bind values
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':user_id', $user_id);

        // Execute
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function getUsersInAccount($id){
        $this->db->query('SELECT u.id, u.fname, u.lname FROM app_user u JOIN account_user au ON u.id = au.user_id WHERE au.account_id = :id');
        $this->db->bind(':id', $id);

        $results = $this->db->resultSet();

        return $results;
    }

    public function getAccountById($id){
        $this->db->query('SELECT a.*, u.username, u.fname, u.lname FROM account a JOIN app_user u ON a.created_by = u.id WHERE a.id = :id');
        $this->db->bind(':id', $id);

        $row = $this->db->single();

        return $row;
    }

    public function updateAccount($account_id, $new_name, $new_currency){
        $this->db->query('UPDATE account SET account_name = :account_name, currency = :currency WHERE id = :id');
        $this->db->bind(':account_name', $new_name);
        $this->db->bind(':currency', $new_currency);
        $this->db->bind(':id', $account_id);

        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function createInvitation($data){
        $this->db->query('INSERT INTO invitation (account_id, invited_by, token, expires_at) VALUES (:account_id, :invited_by, :token, :expires_at)');
        $this->db->bind(':account_id', $data['account_id']);
        $this->db->bind(':invited_by', $data['invited_by']);
        $this->db->bind(':token', $data['token']);
        $this->db->bind(':expires_at', $data['expires_at']);

        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function getInvitationByToken($token){
        $this->db->query('SELECT * FROM invitation WHERE token = :token');
        $this->db->bind(':token', $token);
        $row = $this->db->single();
        return $row;
    }

    public function isUserInAccount($account_id, $user_id){
        $this->db->query('SELECT * FROM account_user WHERE account_id = :account_id AND user_id = :user_id');
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':user_id', $user_id);
        $row = $this->db->single();
        if($this->db->rowCount() > 0){
            return true;
        } else {
            return false;
        }
    }

    public function markInvitationAsAccepted($invitation_id){
        $this->db->query('UPDATE invitation SET accepted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $this->db->bind(':id', $invitation_id);
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function deleteAccount($id){
        $this->db->beginTransaction();
        try {
            // Delete from account - ON DELETE CASCADE will handle the rest
            $this->db->query('DELETE FROM account WHERE id = :id');
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            // For debugging, you might want to log the error: error_log($e->getMessage());
            return false;
        }
    }

    public function removeUserFromAccount($account_id, $user_id){
        $this->db->query('DELETE FROM account_user WHERE account_id = :account_id AND user_id = :user_id');
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':user_id', $user_id);

        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
    }

    public function userHasFinancialHistory($accountId, $userId){
        // Check if the user is a payer in any receipt within the account
        $this->db->query('SELECT COUNT(*) as count FROM receipt WHERE account_id = :account_id AND payer_id = :user_id');
        $this->db->bind(':account_id', $accountId);
        $this->db->bind(':user_id', $userId);
        $payerCount = $this->db->single()->count;

        if ($payerCount > 0) {
            return true;
        }

        // Check if the user is a beneficiary in any item within the account
        $this->db->query('
            SELECT COUNT(*) as count 
            FROM item_user iu
            JOIN item i ON iu.item_id = i.id
            JOIN receipt r ON i.receipt_id = r.id
            WHERE r.account_id = :account_id AND iu.user_id = :user_id
        ');
        $this->db->bind(':account_id', $accountId);
        $this->db->bind(':user_id', $userId);
        $beneficiaryCount = $this->db->single()->count;

        return $beneficiaryCount > 0;
    }
}

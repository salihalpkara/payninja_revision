<?php
class Receipt extends Model {
    public function __construct(){
        parent::__construct();
    }

    public function getReceiptsByAccountId($account_id){
        $this->db->query("
            SELECT 
                r.id, 
                r.receipt_date_time, 
                r.location, 
                r.total_amount, 
                r.currency, 
                u.fname AS payer_fname, 
                u.lname AS payer_lname,
                GROUP_CONCAT(DISTINCT CONCAT(i.id, '---PART_SEP---', i.name, '---PART_SEP---', i.price, '---PART_SEP---', i.quantity, '---PART_SEP---', 
                    (SELECT GROUP_CONCAT(CONCAT(au.fname, ' ', au.lname) ORDER BY au.fname SEPARATOR ', ') 
                     FROM item_user iu_inner JOIN app_user au ON iu_inner.user_id = au.id WHERE iu_inner.item_id = i.id)
                ) SEPARATOR '---ITEM_SEP---') AS items_data,
                GROUP_CONCAT(DISTINCT rn.note SEPARATOR '||') AS notes_data
            FROM 
                receipt r 
            JOIN 
                app_user u ON r.payer_id = u.id 
            LEFT JOIN 
                item i ON r.id = i.receipt_id
            LEFT JOIN
                receipt_note rn ON r.id = rn.receipt_id
            WHERE 
                r.account_id = :account_id
            GROUP BY
                r.id, r.receipt_date_time, r.location, r.total_amount, r.currency, u.fname, u.lname
            ORDER BY 
                r.receipt_date_time DESC
        ");
        $this->db->bind(':account_id', $account_id);
        $results = $this->db->resultSet();

        // Process results to structure items and notes
        foreach ($results as &$receipt) {
            $receipt->items = [];
            if (!empty($receipt->items_data)) {
                $items_raw = explode('---ITEM_SEP---', $receipt->items_data);
                foreach ($items_raw as $item_str) {
                    $parts = explode('---PART_SEP---', $item_str, 5); // Limit to 5 parts to handle names with colons
                    if (count($parts) >= 4) {
                        $item_id = $parts[0];
                        $name = $parts[1];
                        $price = $parts[2];
                        $quantity = $parts[3];
                        $bought_for_names = isset($parts[4]) ? $parts[4] : '';
                        $receipt->items[] = (object)[
                            'id' => $item_id,
                            'name' => $name,
                            'price' => $price,
                            'quantity' => $quantity,
                            'bought_for_names' => $bought_for_names
                        ];
                    }
                }
            }
            unset($receipt->items_data);

            $receipt->notes = [];
            if (!empty($receipt->notes_data)) {
                $notes_raw = explode('|||', $receipt->notes_data);
                foreach ($notes_raw as $note_str) {
                    $receipt->notes[] = $note_str;
                }
            }
            unset($receipt->notes_data);
        }
        return $results;
    }

    public function getLocationsByAccountId($account_id){
        $this->db->query('SELECT DISTINCT location FROM receipt WHERE account_id = :account_id ORDER BY location');
        $this->db->bind(':account_id', $account_id);
        $results = $this->db->resultSet();
        return $results;
    }

    public function getCurrenciesByAccountId($account_id, $default_currency){
        $this->db->query('SELECT DISTINCT currency FROM receipt WHERE account_id = :account_id AND currency != :default_currency ORDER BY currency');
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':default_currency', $default_currency);
        $results = $this->db->resultSet();
        
        $currencies = array_column($results, 'currency');
        array_unshift($currencies, $default_currency);
        return array_unique($currencies);
    }

    public function addReceiptWithItems($data){
        $this->db->beginTransaction();
        try {
            // Insert Receipt
            $this->db->query('INSERT INTO receipt (account_id, payer_id, location, total_amount, receipt_date_time, currency) VALUES (:account_id, :payer_id, :location, :total_amount, :receipt_date_time, :currency)');
            $this->db->bind(':account_id', $data['account_id']);
            $this->db->bind(':payer_id', $data['payer_id']);
            $this->db->bind(':location', $data['location']);
            $this->db->bind(':total_amount', $data['total_amount']);
            $this->db->bind(':receipt_date_time', $data['receipt_date_time']);
            $this->db->bind(':currency', $data['currency']);
            $this->db->execute();
            $receipt_id = $this->db->lastInsertId();

            // Insert Receipt Note (if any)
            if (!empty($data['receipt_note'])) {
                $this->db->query('INSERT INTO receipt_note (receipt_id, note) VALUES (:receipt_id, :note)');
                $this->db->bind(':receipt_id', $receipt_id);
                $this->db->bind(':note', $data['receipt_note']);
                $this->db->execute();
            }

            // Insert Items and Item Users
            if (!empty($data['item_name']) && is_array($data['item_name'])) {
                foreach ($data['item_name'] as $index => $itemName) {
                    if (!empty(trim($itemName))) {
                        $this->db->query('INSERT INTO item (receipt_id, name, price, quantity) VALUES (:receipt_id, :name, :price, :quantity)');
                        $this->db->bind(':receipt_id', $receipt_id);
                        $this->db->bind(':name', trim($itemName));
                        $this->db->bind(':price', floatval($data['item_price'][$index]));
                        $this->db->bind(':quantity', intval($data['amount'][$index]));
                        $this->db->execute();
                        $item_id = $this->db->lastInsertId();

                        // Insert Item Users
                        if (isset($data['bought_for'][$index]) && is_array($data['bought_for'][$index])) {
                            foreach ($data['bought_for'][$index] as $user_id_for_item) {
                                if (!empty($user_id_for_item)) {
                                    $this->db->query('INSERT INTO item_user (item_id, user_id) VALUES (:item_id, :user_id)');
                                    $this->db->bind(':item_id', $item_id);
                                    $this->db->bind(':user_id', intval($user_id_for_item));
                                    $this->db->execute();
                                }
                            }
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error adding receipt with items: " . $e->getMessage());
            return false;
        }
    }

    public function getReceiptById($id){
        $this->db->query('SELECT r.*, u.username as payer_name FROM receipt r JOIN app_user u ON r.payer_id = u.id WHERE r.id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    public function getItemsByReceiptId($id){
        $this->db->query('SELECT * FROM item WHERE receipt_id = :id');
        $this->db->bind(':id', $id);
        $results = $this->db->resultSet();
        return $results;
    }

    public function getNotesByReceiptId($id){
        $this->db->query('SELECT * FROM receipt_note WHERE receipt_id = :id ORDER BY created_at DESC');
        $this->db->bind(':id', $id);
        $results = $this->db->resultSet();
        return $results;
    }

    public function getLatestReceiptsByAccountId($account_id, $limit){
        $this->db->query('SELECT r.location, r.created_at, r.payer_id, r.total_amount, r.currency FROM receipt r WHERE r.account_id = :account_id ORDER BY r.created_at DESC LIMIT :limit');
        $this->db->bind(':account_id', $account_id);
        $this->db->bind(':limit', $limit);
        $results = $this->db->resultSet();
        return $results;
    }

    public function getAccountStatistics($account_id, $beneficiarySubsets){
        $statsSql = "
            WITH ItemBeneficiarySets AS (
                SELECT
                    i.id AS item_id,
                    GROUP_CONCAT(DISTINCT iu.user_id ORDER BY iu.user_id SEPARATOR ',') AS beneficiary_set_signature
                FROM
                    item i
                JOIN
                    item_user iu ON i.id = iu.item_id
                JOIN
                    receipt r_filter ON i.receipt_id = r_filter.id AND r_filter.account_id = :account_id_cte
                GROUP BY
                    i.id
            )
            SELECT
                r.payer_id,
                ibs.beneficiary_set_signature,
                r.currency,
                SUM(i.price) AS total_amount
            FROM
                receipt r
            JOIN
                item i ON r.id = i.receipt_id
            JOIN
                ItemBeneficiarySets ibs ON i.id = ibs.item_id
            GROUP BY
                r.payer_id,
                ibs.beneficiary_set_signature,
                r.currency
            ORDER BY
                r.payer_id,
                ibs.beneficiary_set_signature,
                r.currency;
        ";
        $this->db->query($statsSql);
        $this->db->bind(':account_id_cte', $account_id);
        $results = $this->db->resultSet();

        $statistics = [];
        foreach ($results as $row) {
            $payer_id = $row->payer_id;
            $signature = $row->beneficiary_set_signature ?? 'unknown';
            $currency = $row->currency;
            $total_amount = $row->total_amount;

            if (!isset($statistics[$payer_id])) {
                $statistics[$payer_id] = [];
            }
            if (!isset($statistics[$payer_id][$signature])) {
                $statistics[$payer_id][$signature] = [];
            }
            $statistics[$payer_id][$signature][$currency] = $total_amount;
        }
        return $statistics;
    }

    public function getPaidForOthersStatistics($account_id){
        $paidForOthersSql = "
            SELECT
                r.payer_id,
                r.currency,
                SUM(
                    -- Calculate the cost share for each beneficiary
                    i.price / (SELECT COUNT(DISTINCT iu_count.user_id) FROM item_user iu_count WHERE iu_count.item_id = i.id)
                ) AS total_paid_for_others
            FROM
                receipt r
            JOIN
                item i ON r.id = i.receipt_id
            JOIN
                item_user iu ON i.id = iu.item_id  -- To fetch each user benefiting from the item
            WHERE
                r.account_id = :account_id
                AND iu.user_id != r.payer_id      -- Only sum shares for beneficiaries OTHER THAN the payer
            GROUP BY
                r.payer_id,
                r.currency
            ORDER BY
                r.payer_id,
                r.currency;
        ";
        $this->db->query($paidForOthersSql);
        $this->db->bind(':account_id', $account_id);
        $results = $this->db->resultSet();

        $paidForOthersStats = [];
        foreach ($results as $row) {
            $payer_id = $row->payer_id;
            $currency = $row->currency;
            $total = $row->total_paid_for_others;

            if (!isset($paidForOthersStats[$payer_id])) {
                $paidForOthersStats[$payer_id] = [];
            }
            $paidForOthersStats[$payer_id][$currency] = $total;
        }
        return $paidForOthersStats;
    }

    public function deleteReceipt($id){
        $this->db->beginTransaction();
        try {
            // Delete associated item_user entries
            $this->db->query('DELETE iu FROM item_user iu JOIN item i ON iu.item_id = i.id WHERE i.receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $id);
            $this->db->execute();

            // Delete associated items
            $this->db->query('DELETE FROM item WHERE receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $id);
            $this->db->execute();

            // Delete associated receipt_note
            $this->db->query('DELETE FROM receipt_note WHERE receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $id);
            $this->db->execute();

            // Delete the receipt itself
            $this->db->query('DELETE FROM receipt WHERE id = :id');
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting receipt: " . $e->getMessage());
            return false;
        }
    }

    public function getReceiptDetailsForEdit($receipt_id){
        $this->db->query("
            SELECT 
                r.id, 
                r.account_id,
                r.receipt_date_time, 
                r.location, 
                r.total_amount, 
                r.currency, 
                r.payer_id,
                rn.note AS receipt_note,
                GROUP_CONCAT(DISTINCT CONCAT(i.id, '---PART_SEP---', i.name, '---PART_SEP---', i.price, '---PART_SEP---', i.quantity, '---PART_SEP---', 
                    (SELECT GROUP_CONCAT(iu_inner.user_id ORDER BY iu_inner.user_id SEPARATOR '--USER_SEP--') 
                     FROM item_user iu_inner WHERE iu_inner.item_id = i.id)
                ) SEPARATOR '---ITEM_SEP---') AS items_data
            FROM 
                receipt r 
            LEFT JOIN 
                receipt_note rn ON r.id = rn.receipt_id
            LEFT JOIN 
                item i ON r.id = i.receipt_id
            WHERE 
                r.id = :receipt_id
            GROUP BY
                r.id, r.account_id, r.receipt_date_time, r.location, r.total_amount, r.currency, r.payer_id, rn.note
        ");
        $this->db->bind(':receipt_id', $receipt_id);
        $result = $this->db->single();

        if ($result) {
            $result->items = [];
            if (!empty($result->items_data)) {
                $items_raw = explode('---ITEM_SEP---', $result->items_data);
                foreach ($items_raw as $item_str) {
                    $parts = explode('---PART_SEP---', $item_str, 5);
                    if (count($parts) >= 5) {
                        $item_id = $parts[0];
                        $name = $parts[1];
                        $price = $parts[2];
                        $quantity = $parts[3];
                        $bought_for_users_str = $parts[4];
                        $bought_for_users = !empty($bought_for_users_str) ? explode('--USER_SEP--', $bought_for_users_str) : [];
                        $result->items[] = (object)[
                            'id' => $item_id,
                            'name' => $name,
                            'price' => $price,
                            'quantity' => $quantity,
                            'bought_for_users' => $bought_for_users
                        ];
                    }
                }
            }
            unset($result->items_data);
        }
        return $result;
    }

    public function updateReceiptWithItems($data){
        $this->db->beginTransaction();
        try {
            // Update Receipt
            $this->db->query('UPDATE receipt SET location = :location, total_amount = :total_amount, receipt_date_time = :receipt_date_time, currency = :currency, payer_id = :payer_id WHERE id = :receipt_id');
            $this->db->bind(':location', $data['location']);
            $this->db->bind(':total_amount', $data['total_amount']);
            $this->db->bind(':receipt_date_time', $data['receipt_date_time']);
            $this->db->bind(':currency', $data['currency']);
            $this->db->bind(':payer_id', $data['payer_id']);
            $this->db->bind(':receipt_id', $data['receipt_id']);
            $this->db->execute();

            // Update or Insert Receipt Note
            $this->db->query('SELECT COUNT(*) FROM receipt_note WHERE receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $data['receipt_id']);
            $noteExists = $this->db->single()->{'COUNT(*)'};

            if (!empty($data['receipt_note'])) {
                if ($noteExists > 0) {
                    $this->db->query('UPDATE receipt_note SET note = :note WHERE receipt_id = :receipt_id');
                } else {
                    $this->db->query('INSERT INTO receipt_note (receipt_id, note) VALUES (:receipt_id, :note)');
                }
                $this->db->bind(':receipt_id', $data['receipt_id']);
                $this->db->bind(':note', $data['receipt_note']);
                $this->db->execute();
            } elseif ($noteExists > 0) {
                // If note is empty and existed before, delete it
                $this->db->query('DELETE FROM receipt_note WHERE receipt_id = :receipt_id');
                $this->db->bind(':receipt_id', $data['receipt_id']);
                $this->db->execute();
            }

            // Delete all existing items and their associations for this receipt
            $this->db->query('DELETE iu FROM item_user iu JOIN item i ON iu.item_id = i.id WHERE i.receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $data['receipt_id']);
            $this->db->execute();

            $this->db->query('DELETE FROM item WHERE receipt_id = :receipt_id');
            $this->db->bind(':receipt_id', $data['receipt_id']);
            $this->db->execute();

            // Process items (insert all as new)
            if (!empty($data['item_name']) && is_array($data['item_name'])) {
                foreach ($data['item_name'] as $index => $itemName) {
                    $name = trim($itemName);
                    $price = floatval($data['item_price'][$index]);
                    $quantity = intval($data['amount'][$index]);
                    $bought_for_users = isset($data['bought_for'][$index]) ? $data['bought_for'][$index] : [];

                    if (!empty($name)) {
                        // Insert new item
                        $this->db->query('INSERT INTO item (receipt_id, name, price, quantity) VALUES (:receipt_id, :name, :price, :quantity)');
                        $this->db->bind(':receipt_id', $data['receipt_id']);
                        $this->db->bind(':name', $name);
                        $this->db->bind(':price', $price);
                        $this->db->bind(':quantity', $quantity);
                        $this->db->execute();
                        $item_id = $this->db->lastInsertId();

                        // Insert new associations
                        foreach ($bought_for_users as $user_id) {
                            if (!empty($user_id)) {
                                $this->db->query('INSERT INTO item_user (item_id, user_id) VALUES (:item_id, :user_id)');
                                $this->db->bind(':item_id', $item_id);
                                $this->db->bind(':user_id', intval($user_id));
                                $this->db->execute();
                            }
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating receipt with items: " . $e->getMessage());
            return false;
        }
    }

    public function addReceiptWithItemsFromAPI($data){
        $this->db->beginTransaction();
        try {
            // 1. Insert the main receipt record
            $this->db->query('INSERT INTO receipt (account_id, payer_id, location, total_amount, receipt_date_time, currency) VALUES (:account_id, :payer_id, :location, :total_amount, :receipt_date_time, :currency)');
            $this->db->bind(':account_id', $data['account_id']);
            $this->db->bind(':payer_id', $data['payer_id']);
            $this->db->bind(':location', $data['location']);
            $this->db->bind(':total_amount', $data['total_amount']);
            $this->db->bind(':receipt_date_time', $data['receipt_date_time']);
            $this->db->bind(':currency', $data['currency']);
            $this->db->execute();
            $receipt_id = $this->db->lastInsertId();

            // 2. Insert items and their user associations
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    // Insert the item
                    $this->db->query('INSERT INTO item (receipt_id, name, price, quantity) VALUES (:receipt_id, :name, :price, :quantity)');
                    $this->db->bind(':receipt_id', $receipt_id);
                    $this->db->bind(':name', trim($itemData['name']));
                    $this->db->bind(':price', floatval($itemData['price']));
                    $this->db->bind(':quantity', 1); // Default quantity to 1 as it's not provided by the test script
                    $this->db->execute();
                    $item_id = $this->db->lastInsertId();

                    // Associate the item with the beneficiaries
                    if (!empty($itemData['beneficiaries']) && is_array($itemData['beneficiaries'])) {
                        foreach ($itemData['beneficiaries'] as $userId) {
                            $this->db->query('INSERT INTO item_user (item_id, user_id) VALUES (:item_id, :user_id)');
                            $this->db->bind(':item_id', $item_id);
                            $this->db->bind(':user_id', $userId);
                            $this->db->execute();
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            // Log the error for debugging purposes.
            error_log("API Error adding receipt with items: " . $e->getMessage());
            return false;
        }
    }
}
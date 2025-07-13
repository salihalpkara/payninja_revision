import os
import time
import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys

# --- Configuration ---
BASE_URL = "http://localhost/payninja_ai_revision"
RESULTS_DIR = "test_results"

# --- Helper Functions ---

def setup_driver():
    """Sets up the Chrome WebDriver to run in headless mode."""
    options = webdriver.ChromeOptions()
    options.add_argument("--headless")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--start-maximized")
    driver = webdriver.Chrome(service=ChromeService(ChromeDriverManager().install()), options=options)
    print("WebDriver set up in headless mode.")
    return driver

def create_results_folder():
    """Creates a timestamped folder for the test results."""
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
    folder_path = os.path.join(RESULTS_DIR, f"test_run_{timestamp}")
    os.makedirs(folder_path, exist_ok=True)
    print(f"Results will be saved in: {folder_path}")
    return folder_path

def capture_page(driver, folder_path, step_name):
    """Captures a screenshot and the HTML source of the current page."""
    try:
        # Wait for page to be reasonably loaded
        WebDriverWait(driver, 5).until(
            lambda d: d.execute_script('return document.readyState') == 'complete'
        )
        
        # Sanitize step_name for filename
        filename = "".join(c if c.isalnum() else "_" for c in step_name)
        
        # Capture Screenshot
        screenshot_path = os.path.join(folder_path, f"{filename}.png")
        driver.save_screenshot(screenshot_path)
        
        # Save HTML
        html_path = os.path.join(folder_path, f"{filename}.html")
        with open(html_path, "w", encoding="utf-8") as f:
            f.write(driver.page_source)
            
        print(f"  - Captured: {step_name}")
    except Exception as e:
        print(f"  - Error capturing page for step '{step_name}': {e}")

def generate_unique_user(base_username):
    """Generates a unique username, email, fname, and lname."""
    timestamp = int(time.time())
    return {
        "username": f"{base_username}_{timestamp}",
        "email": f"{base_username}_{timestamp}@test.com",
        "fname": base_username.capitalize(),
        "lname": "Tester"
    }

# --- Test Steps ---

def run_tests():
    """Main function to run the entire test suite."""
    driver = setup_driver()
    results_folder = create_results_folder()
    
    # Generate unique users for this test run
    user1 = generate_unique_user("userone")
    user2 = generate_unique_user("usertwo")

    try:
        # --- Step 1: Register User 1 ---
        print("\n--- Starting: Register User 1 ---")
        driver.get(f"{BASE_URL}/users/register")
        
        # Verify we are on the registration page
        try:
            WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.NAME, "fname")))
            print("Successfully loaded registration page.")
        except TimeoutException:
            print(f'''Error: Failed to load the registration page. The page might have been redirected. Current URL: {driver.current_url}
Page Source:
{driver.page_source}''')
            capture_page(driver, results_folder, "error_register_page_load_failed")
            raise

        capture_page(driver, results_folder, "01_register_page")
        driver.find_element(By.NAME, "username").send_keys(user1["username"])
        driver.find_element(By.NAME, "fname").send_keys(user1["fname"])
        driver.find_element(By.NAME, "lname").send_keys(user1["lname"])
        driver.find_element(By.NAME, "email").send_keys(user1["email"])
        driver.find_element(By.NAME, "password").send_keys("password123")
        driver.find_element(By.NAME, "confirm_password").send_keys("password123")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        # --- Step 2: Login with User 1 ---
        print("\n--- Starting: Login User 1 ---")
        WebDriverWait(driver, 10).until(EC.url_contains("users/login"))
        capture_page(driver, results_folder, "02_login_page_for_user1")
        driver.find_element(By.NAME, "username").send_keys(user1["username"])
        driver.find_element(By.NAME, "password").send_keys("password123")
        capture_page(driver, results_folder, "02b_login_page_filled")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        
        print("Login button clicked. Waiting for redirection to dashboard...")
        try:
            WebDriverWait(driver, 10).until(EC.url_contains("accounts"))
            print("Successfully redirected to accounts dashboard.")
        except TimeoutException:
            print("Error: Failed to redirect to accounts dashboard after 10 seconds.")
            print(f"Current URL is: {driver.current_url}")
            capture_page(driver, results_folder, "error_login_failed")
            raise # re-raise the exception to stop the script

        # --- Step 3: Create Account ---
        print("\n--- Starting: Create Account ---")
        capture_page(driver, results_folder, "03_accounts_dashboard_empty")
        driver.find_element(By.CSS_SELECTOR, "button[data-bs-target='#addAccountModal']").click()
        time.sleep(1) # Wait for modal animation
        capture_page(driver, results_folder, "04_create_account_modal")
        driver.find_element(By.NAME, "account_name").send_keys("Test Shared Account")
        driver.find_element(By.NAME, "currency").send_keys("USD")
        driver.find_element(By.XPATH, "//div[@id='addAccountModal']//button[@type='submit']").click()

        # --- Step 4: Add Receipt ---
        print("\n--- Starting: Add Receipt ---")
        WebDriverWait(driver, 10).until(EC.url_contains("accounts"))
        capture_page(driver, results_folder, "05_accounts_dashboard_with_account")
        driver.find_element(By.PARTIAL_LINK_TEXT, "View").click()
        WebDriverWait(driver, 10).until(EC.url_contains("accounts/show"))
        capture_page(driver, results_folder, "06_account_details_page")
        driver.find_element(By.LINK_TEXT, "Add Receipt").click()
        WebDriverWait(driver, 10).until(EC.url_contains("receipts/add"))
        
        # Verify we are on the add receipt page
        try:
            WebDriverWait(driver, 5).until(EC.presence_of_element_located((By.ID, "receipt_date_time")))
            print("Successfully loaded add receipt page.")
        except TimeoutException:
            print("Error: Failed to load the add receipt page.")
            capture_page(driver, results_folder, "error_add_receipt_page_load_failed")
            raise

        capture_page(driver, results_folder, "07_add_receipt_page")
        driver.find_element(By.ID, "location").send_keys("Test Supermarket")
        driver.find_element(By.ID, "receipt_date_time").send_keys("2025-07-04T12:00")
        # Add first item
        driver.find_element(By.ID, "item_name_itemDetail_0").send_keys("Groceries")
        driver.find_element(By.ID, "item_price_itemDetail_0").send_keys("50.00")
        # Add second item
        driver.find_element(By.XPATH, "//button[contains(text(),'Add Another Item')]").click()
        driver.find_element(By.ID, "item_name_itemDetail_1").send_keys("Drinks")
        driver.find_element(By.ID, "item_price_itemDetail_1").send_keys("25.50")
        driver.find_element(By.ID, "totalAmount").send_keys("75.50")
        driver.find_element(By.ID, "createReceiptBtn").click()

        # Wait for the confirmation modal to appear and be clickable
        WebDriverWait(driver, 10).until(EC.element_to_be_clickable((By.ID, "confirmYes"))).click()
        
        # --- Step 5: Logout User 1 ---
        print("\n--- Starting: Logout User 1 ---")
        WebDriverWait(driver, 10).until(EC.url_contains("accounts/show"))
        capture_page(driver, results_folder, "08_account_details_after_receipt")
        try:
            # Wait for the dropdown toggle to be clickable and click it
            dropdown_toggle = WebDriverWait(driver, 10).until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, ".dropdown-toggle"))
            )
            dropdown_toggle.click()
            capture_page(driver, results_folder, "08a_dropdown_clicked")

            # Wait for the logout link to be clickable and click it
            logout_link = WebDriverWait(driver, 10).until(
                EC.element_to_be_clickable((By.LINK_TEXT, "Logout"))
            )
            logout_link.click()
            capture_page(driver, results_folder, "08b_logout_link_clicked")

            # Wait for the URL to change to the login page
            WebDriverWait(driver, 10).until(EC.url_contains("users/login"))
        except TimeoutException:
            print("Error: Logout failed - Timeout waiting for elements or URL change.")
            capture_page(driver, results_folder, "error_logout_timeout")
            raise
        except Exception as e:
            print(f"Error during logout: {e}")
            capture_page(driver, results_folder, "error_logout_general")
            raise

        # --- Step 6: Register User 2 ---
        print("\n--- Starting: Register User 2 ---")
        WebDriverWait(driver, 10).until(EC.url_contains("users/login"))
        driver.get(f"{BASE_URL}/users/register")
        capture_page(driver, results_folder, "09_register_page_user2")
        driver.find_element(By.NAME, "username").send_keys(user2["username"])
        driver.find_element(By.NAME, "fname").send_keys(user2["fname"])
        driver.find_element(By.NAME, "lname").send_keys(user2["lname"])
        driver.find_element(By.NAME, "email").send_keys(user2["email"])
        driver.find_element(By.NAME, "password").send_keys("password456")
        driver.find_element(By.NAME, "confirm_password").send_keys("password456")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        # --- Step 7: Login User 1 again to add User 2 ---
        print("\n--- Starting: Login User 1 (again) ---")
        WebDriverWait(driver, 10).until(EC.url_contains("users/login"))
        driver.find_element(By.NAME, "username").send_keys(user1["username"])
        driver.find_element(By.NAME, "password").send_keys("password123")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        WebDriverWait(driver, 10).until(EC.url_contains("accounts"))
        
        # --- Step 8: Add User 2 to Account ---
        print("\n--- Starting: Add User 2 to Account ---")
        driver.find_element(By.PARTIAL_LINK_TEXT, "View").click()
        WebDriverWait(driver, 10).until(EC.url_contains("accounts/show"))
        capture_page(driver, results_folder, "10_account_details_before_add_user")
        try:
            driver.find_element(By.CSS_SELECTOR, "button[data-bs-target='#addUserModal']").click()
            time.sleep(1) # Wait for modal animation
            search_box = driver.find_element(By.ID, "usernameSearch")
            search_box.send_keys(user2["username"])
            time.sleep(2) # Wait for search results to appear
            driver.find_element(By.CSS_SELECTOR, "#userSearchResults button").click()
            WebDriverWait(driver, 10).until(EC.alert_is_present())
            driver.switch_to.alert.accept()
        except:
            pass
        
        # --- Step 9: View and Edit Profile ---
        print("\n--- Starting: View and Edit Profile ---")
        WebDriverWait(driver, 10).until(EC.url_contains("accounts/show"))
        capture_page(driver, results_folder, "11_account_details_after_add_user")
        driver.find_element(By.CSS_SELECTOR, ".dropdown-toggle").click()
        driver.find_element(By.LINK_TEXT, "View profile").click()
        WebDriverWait(driver, 10).until(EC.url_contains("users/profile"))
        capture_page(driver, results_folder, "12_profile_page")
        driver.find_element(By.LINK_TEXT, "Edit Profile").click()
        WebDriverWait(driver, 10).until(EC.url_contains("users/edit"))
        capture_page(driver, results_folder, "13_edit_profile_page")
        fname_field = driver.find_element(By.NAME, "fname")
        fname_field.clear()
        fname_field.send_keys("UserOne-Edited")
        driver.find_element(By.CSS_SELECTOR, "input[type='submit']").click()
        WebDriverWait(driver, 10).until(EC.url_contains("users/profile"))
        capture_page(driver, results_folder, "14_profile_page_after_edit")

        # --- Step 10: Delete Account ---
        print("\n--- Starting: Delete Account ---")
        driver.get(f"{BASE_URL}/accounts")
        WebDriverWait(driver, 10).until(EC.url_contains("accounts"))
        driver.find_element(By.PARTIAL_LINK_TEXT, "View").click()
        WebDriverWait(driver, 10).until(EC.url_contains("accounts/show"))
        capture_page(driver, results_folder, "15_account_details_before_delete")
        driver.find_element(By.ID, "deleteAccount").click()
        WebDriverWait(driver, 10).until(EC.alert_is_present())
        driver.switch_to.alert.accept()
        WebDriverWait(driver, 10).until(EC.url_contains("accounts"))
        capture_page(driver, results_folder, "16_dashboard_after_delete")
        
        print("\n--- Test run completed successfully! ---")

    except Exception as e:
        print(f"\n--- An error occurred during the test: {e} ---")
        capture_page(driver, results_folder, "error_page")
    finally:
        driver.quit()
        print("\nBrowser closed.")


if __name__ == "__main__":
    run_tests()
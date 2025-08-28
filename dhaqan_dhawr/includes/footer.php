    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Popup -->
    <div id="loginPopup" class="popup-overlay">
        <div class="popup-content">
            <div class="popup-header">
                <h2>Sign In</h2>
                <button onclick="closePopup('loginPopup')" class="close-btn">&times;</button>
            </div>
            
            <form action="Auth/process_login.php" method="POST" class="popup-form">
                <div class="form-group">
                    <label for="login_email">Email Address</label>
                    <input type="email" id="login_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
            
            <div class="popup-footer">
                <p>Don't have an account? <a href="#" onclick="switchToSignup()">Sign up here</a></p>
            </div>
        </div>
    </div>

    <!-- Signup Popup -->
    <div id="signupPopup" class="popup-overlay">
        <div class="popup-content">
            <div class="popup-header">
                <h2>Create Account</h2>
                <button onclick="closePopup('signupPopup')" class="close-btn">&times;</button>
            </div>
            
            <form action="Auth/process_register.php" method="POST" class="popup-form">
                <div class="form-group">
                    <label for="signup_name">Full Name</label>
                    <input type="text" id="signup_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_email">Email Address</label>
                    <input type="email" id="signup_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_phone">Phone Number</label>
                    <input type="tel" id="signup_phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="signup_role">Account Type</label>
                    <select id="signup_role" name="role" required>
                        <option value="buyer">Buyer</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="signup_confirm_password">Confirm Password</label>
                    <input type="password" id="signup_confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Create Account</button>
            </form>
            
            <div class="popup-footer">
                <p>Already have an account? <a href="#" onclick="switchToLogin()">Sign in here</a></p>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="<?= isset($base_url) ? $base_url : '' ?>assets/js/main.js"></script>
</body>
</html>

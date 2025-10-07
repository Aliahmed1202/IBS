// Authentication Controller - Handles user authentication and authorization
class AuthController {
    constructor() {
        this.currentUser = null;
        this.sessionTimeout = 30 * 60 * 1000; // 30 minutes
        this.sessionTimer = null;
        this.init();
    }

    // Initialize authentication controller
    init() {
        this.bindEvents();
        this.startSessionMonitoring();
    }

    // Bind authentication events
    bindEvents() {
        // Login form submission
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        // Auto-fill demo credentials
        this.bindDemoCredentials();

        // Password visibility toggle
        this.bindPasswordToggle();

        // Clear lockout button
        document.getElementById('clear-lockout-btn')?.addEventListener('click', () => {
            this.clearLockout();
            this.showLoginSuccess({ getDisplayName: () => 'System' });
            setTimeout(() => this.removeExistingMessages(), 2000);
        });

        // Reset users button
        document.getElementById('reset-users-btn')?.addEventListener('click', () => {
            this.resetUsers();
        });
    }

    // Bind demo credentials click events
    bindDemoCredentials() {
        const demoCredentials = document.querySelector('.demo-credentials');
        if (demoCredentials) {
            demoCredentials.addEventListener('click', (e) => {
                if (e.target.tagName === 'P') {
                    const text = e.target.textContent;
                    if (text.includes('Admin')) {
                        this.fillDemoCredentials('admin', 'admin123', 'admin');
                    } else if (text.includes('Staff')) {
                        this.fillDemoCredentials('staff', 'staff123', 'staff');
                    }
                }
            });
        }
    }

    // Fill demo credentials
    fillDemoCredentials(username, password, role) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = password;
        document.getElementById('role').value = role;
    }

    // Bind password visibility toggle
    bindPasswordToggle() {
        const passwordField = document.getElementById('password');
        if (passwordField) {
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = 'password-toggle';
            toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            
            passwordField.parentNode.style.position = 'relative';
            passwordField.parentNode.appendChild(toggleButton);
            
            toggleButton.addEventListener('click', () => {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                toggleButton.innerHTML = type === 'password' 
                    ? '<i class="fas fa-eye"></i>' 
                    : '<i class="fas fa-eye-slash"></i>';
            });
        }
    }

    // Handle login attempt
    async handleLogin() {
        const loginData = this.getLoginFormData();
        
        if (!this.validateLoginData(loginData)) {
            return;
        }

        this.showLoginLoading(true);

        try {
            const user = User.authenticate(loginData.username, loginData.password, loginData.role);
            
            if (user) {
                await this.handleSuccessfulLogin(user);
            } else {
                this.handleFailedLogin();
            }
        } catch (error) {
            this.handleLoginError(error);
        } finally {
            this.showLoginLoading(false);
        }
    }

    // Get login form data
    getLoginFormData() {
        return {
            username: document.getElementById('username').value.trim(),
            password: document.getElementById('password').value,
            role: document.getElementById('role').value
        };
    }

    // Validate login data
    validateLoginData(data) {
        const errors = [];

        if (!data.username) {
            errors.push('Username is required');
        }

        if (!data.password) {
            errors.push('Password is required');
        }

        if (!data.role) {
            errors.push('Role is required');
        }

        if (data.username && data.username.length < 3) {
            errors.push('Username must be at least 3 characters');
        }

        if (data.password && data.password.length < 6) {
            errors.push('Password must be at least 6 characters');
        }

        if (errors.length > 0) {
            this.showLoginError(errors.join(', '));
            return false;
        }

        return true;
    }

    // Handle successful login
    async handleSuccessfulLogin(user) {
        this.currentUser = user;
        
        // Record login time
        Staff.recordLogin(user.id);
        
        // Store session data
        this.storeSessionData(user);
        
        // Reset session timer
        this.resetSessionTimer();
        
        // Clear login form
        this.clearLoginForm();
        
        // Show success message
        this.showLoginSuccess(user);
        
        // Trigger login event
        this.triggerLoginEvent(user);
    }

    // Handle failed login
    handleFailedLogin() {
        this.showLoginError('Invalid username, password, or role. Please check your credentials and try again.');
        this.clearPasswordField();
        this.incrementFailedAttempts();
    }

    // Handle login error
    handleLoginError(error) {
        console.error('Login error:', error);
        this.showLoginError('An error occurred during login. Please try again.');
    }

    // Store session data
    storeSessionData(user) {
        const sessionData = {
            user: user,
            loginTime: new Date().toISOString(),
            expiresAt: new Date(Date.now() + this.sessionTimeout).toISOString()
        };
        
        sessionStorage.setItem('userSession', JSON.stringify(sessionData));
        localStorage.setItem('lastUser', user.username);
    }

    // Start session monitoring
    startSessionMonitoring() {
        // Check session every minute
        setInterval(() => {
            this.checkSessionValidity();
        }, 60000);

        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                this.resetSessionTimer();
            }, { passive: true });
        });
    }

    // Check session validity
    checkSessionValidity() {
        const sessionData = this.getSessionData();
        
        if (sessionData && new Date() > new Date(sessionData.expiresAt)) {
            this.handleSessionExpiry();
        }
    }

    // Handle session expiry
    handleSessionExpiry() {
        this.logout();
        this.showSessionExpiredMessage();
    }

    // Reset session timer
    resetSessionTimer() {
        if (this.currentUser) {
            const sessionData = this.getSessionData();
            if (sessionData) {
                sessionData.expiresAt = new Date(Date.now() + this.sessionTimeout).toISOString();
                sessionStorage.setItem('userSession', JSON.stringify(sessionData));
            }
        }
    }

    // Check authentication status
    checkAuthStatus() {
        // Clear any existing lockout on page load
        this.clearLockout();
        
        const savedUser = sessionStorage.getItem('currentUser');
        if (savedUser) {
            this.currentUser = JSON.parse(savedUser);
            this.showApp();
            this.updateUserInterface();
            this.navigateToSection('dashboard');
        } else {
            this.hideApp();
        }
    }

    // Clear lockout manually
    clearLockout() {
        localStorage.removeItem('lockoutUntil');
        localStorage.removeItem('failedLoginAttempts');
        this.enableLoginForm();
        this.removeExistingMessages();
    }

    // Reset users to default
    resetUsers() {
        localStorage.removeItem('users');
        const defaultUsers = User.getDefaultUsers();
        console.log('Reset users to default:', defaultUsers);
        this.showLoginSuccess({ getDisplayName: () => 'System' });
        setTimeout(() => this.removeExistingMessages(), 2000);
    }

    // Clear session data
    clearSessionData() {
        sessionStorage.removeItem('userSession');
        sessionStorage.removeItem('currentUser');
    }
    showLoginLoading(loading) {
        const submitButton = document.querySelector('#login-form button[type="submit"]');
        const buttonText = submitButton.querySelector('span') || submitButton;
        
        if (loading) {
            submitButton.disabled = true;
            buttonText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
        } else {
            submitButton.disabled = false;
            buttonText.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        }
    }

    // Show login error
    showLoginError(message) {
        this.removeExistingMessages();
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'message message-error';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        
        const loginForm = document.getElementById('login-form');
        loginForm.insertBefore(errorDiv, loginForm.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => errorDiv.remove(), 5000);
    }

    // Show login success
    showLoginSuccess(user) {
        this.removeExistingMessages();
        
        const successDiv = document.createElement('div');
        successDiv.className = 'message message-success';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>Welcome back, ${user.getDisplayName()}!</span>
        `;
        
        const loginForm = document.getElementById('login-form');
        loginForm.insertBefore(successDiv, loginForm.firstChild);
    }

    // Show session expired message
    showSessionExpiredMessage() {
        const message = document.createElement('div');
        message.className = 'message message-warning';
        message.innerHTML = `
            <i class="fas fa-clock"></i>
            <span>Your session has expired. Please log in again.</span>
        `;
        
        const loginContainer = document.querySelector('.login-card');
        loginContainer.insertBefore(message, loginContainer.firstChild);
        
        setTimeout(() => message.remove(), 5000);
    }

    // Remove existing messages
    removeExistingMessages() {
        document.querySelectorAll('.message').forEach(msg => msg.remove());
    }

    // Clear login form
    clearLoginForm() {
        document.getElementById('login-form').reset();
    }

    // Clear password field only
    clearPasswordField() {
        document.getElementById('password').value = '';
    }

    // Increment failed login attempts
    incrementFailedAttempts() {
        const attempts = parseInt(localStorage.getItem('failedLoginAttempts') || '0') + 1;
        localStorage.setItem('failedLoginAttempts', attempts.toString());
        
        if (attempts >= 5) {
            this.handleTooManyFailedAttempts();
        }
    }

    // Handle too many failed attempts
    handleTooManyFailedAttempts() {
        const lockoutTime = 15 * 60 * 1000; // 15 minutes
        const lockoutUntil = Date.now() + lockoutTime;
        
        localStorage.setItem('lockoutUntil', lockoutUntil.toString());
        this.showLoginError('Too many failed attempts. Please try again in 15 minutes.');
        
        // Disable login form
        this.disableLoginForm();
        
        // Start lockout timer
        this.startLockoutTimer(lockoutTime);
    }

    // Disable login form
    disableLoginForm() {
        const form = document.getElementById('login-form');
        const inputs = form.querySelectorAll('input, select, button');
        inputs.forEach(input => input.disabled = true);
    }

    // Enable login form
    enableLoginForm() {
        const form = document.getElementById('login-form');
        const inputs = form.querySelectorAll('input, select, button');
        inputs.forEach(input => input.disabled = false);
    }

    // Start lockout timer
    startLockoutTimer(duration) {
        const timer = setInterval(() => {
            const lockoutUntil = parseInt(localStorage.getItem('lockoutUntil') || '0');
            const remaining = lockoutUntil - Date.now();
            
            if (remaining <= 0) {
                clearInterval(timer);
                localStorage.removeItem('lockoutUntil');
                localStorage.removeItem('failedLoginAttempts');
                this.enableLoginForm();
                this.removeExistingMessages();
            }
        }, 1000);
    }

    // Trigger login event
    triggerLoginEvent(user) {
        const event = new CustomEvent('userLogin', {
            detail: { user: user }
        });
        document.dispatchEvent(event);
    }

    // Trigger logout event
    triggerLogoutEvent() {
        const event = new CustomEvent('userLogout');
        document.dispatchEvent(event);
    }

    // Get current user
    getCurrentUser() {
        return this.currentUser;
    }

    // Check if user is authenticated
    isAuthenticated() {
        return this.currentUser !== null;
    }

    // Check if user has permission
    hasPermission(permission) {
        return this.currentUser && this.currentUser.hasPermission(permission);
    }

    // Check if user has role
    hasRole(role) {
        return this.currentUser && this.currentUser.role === role;
    }
}

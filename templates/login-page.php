<?php
/**
 * Login Page Template for FeeFlow
 * Save this file as: templates/login-page.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current page URL for redirect
$redirect_url = get_permalink();
?>

<div class="feeflow-login-container">
    <div class="feeflow-login-card">
        <div class="feeflow-login-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#0073aa" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        
        <h2 class="feeflow-login-title">Dashboard Restricted</h2>
        <p class="feeflow-login-subtitle">Please log in to access your FeeFlow dashboard and manage tuition data.</p>
        
        <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
            <div class="feeflow-login-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Invalid username or password. Please try again.
            </div>
        <?php endif; ?>
        
        <form id="feeflow-login-form" class="feeflow-login-form" method="post">
            <div class="feeflow-login-form-group">
                <label for="feeflow-username">Username or Email</label>
                <input 
                    type="text" 
                    id="feeflow-username" 
                    name="log" 
                    class="feeflow-login-input" 
                    placeholder="Enter your username or email"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="feeflow-login-form-group">
                <label for="feeflow-password">Password</label>
                <input 
                    type="password" 
                    id="feeflow-password" 
                    name="pwd" 
                    class="feeflow-login-input" 
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="feeflow-login-remember">
                <label class="feeflow-checkbox-label">
                    <input type="checkbox" name="rememberme" value="forever">
                    <span>Remember Me</span>
                </label>
            </div>
            
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_url); ?>">
            <input type="hidden" name="feeflow_login" value="1">
            <?php wp_nonce_field('feeflow_login_action', 'feeflow_login_nonce'); ?>
            
            <button type="submit" class="feeflow-login-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Log In to Your Account
            </button>
        </form>
        
        <div class="feeflow-login-footer">
            <p>You will be redirected back here after logging in.</p>
            <a href="<?php echo wp_lostpassword_url($redirect_url); ?>" class="feeflow-forgot-password">
                Forgot your password?
            </a>
        </div>
    </div>
</div>

<style>
.feeflow-login-container {
    min-height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 12px;
    margin: 20px 0;
}

.feeflow-login-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    padding: 50px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
}

.feeflow-login-icon {
    margin-bottom: 20px;
}

.feeflow-login-icon svg {
    display: inline-block;
}

.feeflow-login-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 10px 0;
}

.feeflow-login-subtitle {
    font-size: 15px;
    color: #718096;
    margin: 0 0 30px 0;
    line-height: 1.6;
}

.feeflow-login-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.feeflow-login-form {
    text-align: left;
}

.feeflow-login-form-group {
    margin-bottom: 20px;
}

.feeflow-login-form-group label {
    display: block;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    font-size: 14px;
}

.feeflow-login-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
    color: #2d3748;
}

.feeflow-login-input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

.feeflow-login-remember {
    margin-bottom: 25px;
}

.feeflow-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #4a5568;
}

.feeflow-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.feeflow-login-button {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-family: inherit;
}

.feeflow-login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.feeflow-login-button:active {
    transform: translateY(0);
}

.feeflow-login-footer {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e2e8f0;
}

.feeflow-login-footer p {
    font-size: 13px;
    color: #718096;
    margin: 0 0 10px 0;
}

.feeflow-forgot-password {
    color: #0073aa;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: color 0.2s ease;
}

.feeflow-forgot-password:hover {
    color: #005a87;
    text-decoration: underline;
}

@media (max-width: 640px) {
    .feeflow-login-card {
        padding: 40px 25px;
    }
    
    .feeflow-login-title {
        font-size: 24px;
    }
    
    .feeflow-login-subtitle {
        font-size: 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('feeflow-login-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        formData.append('action', 'feeflow_login');
        
        const button = form.querySelector('.feeflow-login-button');
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Logging in...';
        
        // Use WordPress AJAX for login
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect;
            } else {
                window.location.href = '<?php echo add_query_arg('login', 'failed', $redirect_url); ?>';
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = originalText;
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
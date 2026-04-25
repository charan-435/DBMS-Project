<?php
session_start();
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    if ($action === 'signup') {
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if (empty($userId) || empty($password) || empty($fullName) || empty($confirmPassword)) {
            $error = "All fields are required for signup.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            if ($service->signup($fullName, $userId, $password)) {
                $success = "Signup successful! You can now login.";
            } else {
                $error = "UserID already exists or database error.";
            }
        }
    } elseif ($action === 'login') {
        if (empty($userId) || empty($password)) {
            $error = "UserID and Password are required.";
        } else {
            $user = $service->login($userId, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid UserID or Password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Login</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at center, #1a1b23 0%, #0f1117 100%);
        height: 100vh;
        margin: 0;
    }
    .auth-container {
        width: 100%;
        max-width: 400px;
        padding: 2.5rem;
        background: rgba(30, 31, 42, 0.7);
        backdrop-filter: blur(15px);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        animation: fadeIn 0.5s ease-out;
    }
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .auth-header h1 {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 0.5rem;
    }
    .auth-header p {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        letter-spacing: 0.1em;
    }
    .form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        color: var(--text-primary);
        font-family: inherit;
        transition: all 0.2s;
    }
    .form-group input:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 4px rgba(126, 175, 232, 0.1);
    }
    .btn-auth {
        width: 100%;
        padding: 0.85rem;
        background: var(--accent-primary);
        color: var(--bg-dark);
        border: none;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 0.5rem;
    }
    .btn-auth:hover {
        background: var(--accent-hover);
        transform: translateY(-1px);
    }
    .auth-toggle {
        text-align: center;
        margin-top: 1.5rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    .auth-toggle a {
        color: var(--accent-primary);
        font-weight: 600;
        cursor: pointer;
    }
    .error-msg {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        padding: 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .success-msg {
        background: rgba(92, 214, 182, 0.1);
        color: var(--accent-green);
        padding: 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(92, 214, 182, 0.2);
    }
    .hidden { display: none; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <div class="auth-container">
    <div class="auth-header">
      <div class="text-accent uppercase tracking-wider text-xxs font-bold mb-2">AUTHENTICATION</div>
      <h1>The Cinematic <em style="color: var(--accent-primary); font-style: italic;">Lens</em></h1>
      <p id="auth-subtitle">Login to access your analytics dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-msg"><?= $success ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="login-form" method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>UserID</label>
        <input type="text" name="user_id" placeholder="Enter your unique ID" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-auth">Sign In</button>
      <div class="auth-toggle">
        Don't have an account? <a onclick="toggleAuth('signup')">Create one</a>
      </div>
    </form>

    <!-- Signup Form -->
    <form id="signup-form" method="POST" class="hidden">
      <input type="hidden" name="action" value="signup">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="John Doe" required>
      </div>
      <div class="form-group">
        <label>UserID</label>
        <input type="text" name="user_id" placeholder="Choose a unique ID" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" id="signup-pwd" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="signup-cpwd" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-auth">Create Account</button>
      <div class="auth-toggle">
        Already have an account? <a onclick="toggleAuth('login')">Sign in</a>
      </div>
    </form>
  </div>

  <script>
    function toggleAuth(mode) {
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const subtitle = document.getElementById('auth-subtitle');

        if (mode === 'signup') {
            loginForm.classList.add('hidden');
            signupForm.classList.remove('hidden');
            subtitle.innerText = "Join our community of film enthusiasts";
        } else {
            signupForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            subtitle.innerText = "Login to access your analytics dashboard";
        }
    }

    document.getElementById('signup-form').addEventListener('submit', function(e) {
        const pwd = document.getElementById('signup-pwd').value;
        const cpwd = document.getElementById('signup-cpwd').value;
        if (pwd !== cpwd) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
        }
    });
  </script>
</body>
</html>

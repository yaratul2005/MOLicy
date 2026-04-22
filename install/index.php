<?php
session_start();

// If installed, redirect to home
if (file_exists(__DIR__ . '/lock')) {
    header('Location: /');
    exit;
}

$step = $_GET['step'] ?? 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - AntiGravity Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --spring-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
            --bg: #0a0a0f;
            --surface: #1a1a2e;
            --primary: #7c3aed;
            --text: #f8f9fa;
            --muted: #a0a0b0;
            --danger: #ef4444;
            --success: #10b981;
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .container {
            background: var(--surface);
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            animation: fallIn 0.8s var(--spring-bounce) forwards;
            transform: translateY(-50px);
            opacity: 0;
        }
        @keyframes fallIn {
            to { transform: translateY(0); opacity: 1; }
        }
        h1 { font-family: 'Syne', sans-serif; margin-top: 0; font-size: 2rem; color: var(--primary); text-align: center; }
        .step { display: none; }
        .step.active { display: block; animation: fadeScale 0.4s ease forwards; }
        @keyframes fadeScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .btn {
            background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-family: 'Syne', sans-serif; font-weight: 600; cursor: pointer; transition: transform 0.2s var(--spring-bounce), box-shadow 0.2s; width: 100%; font-size: 1.1rem; margin-top: 20px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(124, 58, 237, 0.4); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--muted); }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; font-family: 'DM Sans', sans-serif; box-sizing: border-box; transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: var(--primary); }
        .req-item { display: flex; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; margin-bottom: 8px; }
        .status-ok { color: var(--success); }
        .status-err { color: var(--danger); }
        .error-msg { color: var(--danger); font-size: 0.9rem; margin-top: 5px; display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>AntiGravity Forum</h1>
    
    <div id="step1" class="step active">
        <h2>System Requirements</h2>
        <div id="requirements-list">
            <!-- Loaded via JS -->
        </div>
        <button class="btn" id="btn-next-1" disabled>Checking...</button>
    </div>

    <div id="step2" class="step">
        <h2>Database Configuration</h2>
        <form id="db-form">
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="127.0.0.1" required>
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" required>
            </div>
            <div class="form-group">
                <label>Database User</label>
                <input type="text" name="db_user" required>
            </div>
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass">
            </div>
            <div class="error-msg" id="db-error"></div>
            <button type="submit" class="btn" id="btn-test-db">Test & Create Setup</button>
        </form>
    </div>

    <div id="step3" class="step">
        <h2>Forum Identity</h2>
        <form id="identity-form">
            <div class="form-group">
                <label>Forum Name</label>
                <input type="text" name="forum_name" required>
            </div>
            <div class="form-group">
                <label>Tagline</label>
                <input type="text" name="forum_tagline">
            </div>
            <button type="submit" class="btn">Next Step</button>
        </form>
    </div>

    <div id="step4" class="step">
        <h2>Admin Account</h2>
        <form id="admin-form">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_user" required>
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="admin_email" required>
            </div>
            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="admin_pass" required>
            </div>
            <button type="submit" class="btn">Finalize Installation</button>
        </form>
    </div>

    <div id="step5" class="step">
        <h2>Installation Complete!</h2>
        <p>The AntiGravity Forum has been successfully installed. The installer will now be locked.</p>
        <button class="btn" onclick="window.location.href='/'">Go to Forum</button>
    </div>

</div>

<script>
    // JS Logic for the installer
    document.addEventListener('DOMContentLoaded', () => {
        // Step 1: Check Requirements
        fetch('actions.php?action=check_reqs')
            .then(r => r.json())
            .then(data => {
                let html = '';
                let allOk = true;
                data.reqs.forEach(req => {
                    let statusClass = req.ok ? 'status-ok' : 'status-err';
                    let statusText = req.ok ? 'OK' : 'Fail';
                    html += `<div class="req-item"><span>${req.name}</span><span class="${statusClass}">${statusText}</span></div>`;
                    if (!req.ok) allOk = false;
                });
                document.getElementById('requirements-list').innerHTML = html;
                let btn = document.getElementById('btn-next-1');
                if (allOk) {
                    btn.textContent = 'Continue to Database';
                    btn.disabled = false;
                    btn.onclick = () => showStep(2);
                } else {
                    btn.textContent = 'Please fix errors';
                }
            });

        // Step 2: Database
        document.getElementById('db-form').addEventListener('submit', (e) => {
            e.preventDefault();
            let fd = new FormData(e.target);
            fd.append('action', 'setup_db');
            let btn = document.getElementById('btn-test-db');
            btn.textContent = 'Setting up...';
            btn.disabled = true;

            fetch('actions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showStep(3);
                    } else {
                        let err = document.getElementById('db-error');
                        err.textContent = res.error;
                        err.style.display = 'block';
                        btn.textContent = 'Test & Create Setup';
                        btn.disabled = false;
                    }
                });
        });

        // Step 3: Identity
        document.getElementById('identity-form').addEventListener('submit', (e) => {
            e.preventDefault();
            showStep(4);
        });

        // Step 4: Admin
        document.getElementById('admin-form').addEventListener('submit', (e) => {
            e.preventDefault();
            let fd = new FormData(e.target);
            let idFd = new FormData(document.getElementById('identity-form'));
            for(let pair of idFd.entries()) fd.append(pair[0], pair[1]);
            fd.append('action', 'finalize');

            let btn = e.target.querySelector('button');
            btn.textContent = 'Finalizing...';
            btn.disabled = true;

            fetch('actions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showStep(5);
                    } else {
                        alert("Error: " + res.error);
                        btn.textContent = 'Finalize Installation';
                        btn.disabled = false;
                    }
                });
        });
    });

    function showStep(n) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + n).classList.add('active');
    }
</script>
</body>
</html>

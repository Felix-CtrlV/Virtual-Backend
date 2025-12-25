<?php
$pageTitle = 'Profile Settings';
$pageSubtitle = 'Manage your admin account information and preferences.';
include("partials/nav.php");
?>

<section class="section active">
    <div id="alertBox" class="alert"></div>

    <form action="utils/adminSettingChanges.php" method="post">
        <div class="section-actions" style="justify-content: flex-end;">
            <input class="btn-ghost btn" value="Cancel" type="reset">
            <button name="savebutton" class="btn-primary btn">Save Changes</button>
        </div>
        </div>

        <div class="grid">
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">
                    <div>
                        <div class="card-title">Account Information</div>
                        <div class="card-value" style="font-size: 16px;">
                            <?php echo htmlspecialchars($name); ?>
                        </div>
                    </div>
                    <span class="card-chip">Administrator</span>
                </div>

                <div
                    style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 8px; font-size: 13px;">
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Full Name</label>
                        <input autocomplete="off" name="fullname" type="text"
                            value="<?php echo htmlspecialchars($name); ?>"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Email</label>
                        <input autocomplete="off" name="email" type="email"
                            value="<?php echo htmlspecialchars($admininfo['email'] ?? 'admin@example.com'); ?>"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Username</label>
                        <input autocomplete="off" name="username" type="text"
                            value="<?php echo htmlspecialchars($admininfo['username'] ?? 'admin'); ?>"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Contact Number</label>
                        <input autocomplete="off" name="phone" type="text"
                            value="<?php echo htmlspecialchars($admininfo['phone'] ?? '+63 900 000 0000'); ?>"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Security</div>
                        <div class="card-value" style="font-size: 16px;">Password</div>
                    </div>
                </div>
                <p style="font-size: 12px; color: var(--muted); margin-bottom: 8px;">
                    For security reasons, your current password is hidden. Use this area to update it.
                </p>
                <div style="display:flex; flex-direction:column; gap:8px; font-size: 13px;">
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Current
                            Password</label>
                        <input autocomplete="off" name="current_password" type="password" placeholder="••••••••"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">New Password</label>
                        <input autocomplete="off" name="new_password" type="password" placeholder="New password"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:3px; color: var(--muted);">Confirm New
                            Password</label>
                        <input autocomplete="off" name="confirm_password" type="password" placeholder="Confirm password"
                            style="width: 100%; padding:7px 9px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4); background: rgba(15,23,42,0.9); color: var(--text); font-size: 13px;" />
                    </div>
                </div>
            </div>
    </form>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Preferences</div>
                <div class="card-value" style="font-size: 16px;">Admin Console</div>
            </div>
        </div>
        <div style="font-size: 13px; margin-top: 6px;">
            <p style="margin-bottom: 8px; color: var(--muted);">
                Customize how your admin dashboard behaves (Coming soon).
            </p>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <label style="display:flex; align-items:center; gap:8px;">
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                    <span>Show quick stats on dashboard</span>
                </label>
                <label style="display:flex; align-items:center; gap:8px;">
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                    <span>Dark mode (coming soon)</span>
                </label>
            </div>
        </div>
    </div>
    </div>
</section>

<script src="script.js"></script>

<script>
    function showAlert(msg, status) {
        const alertBox = document.getElementById("alertBox");
        alertBox.classList.remove("error", "success");
        alertBox.classList.add("show");
        alertBox.textContent = msg;
        if(status === "wrong_password" || status === "password_mismatch"){
            alertBox.classList.add("error");
        }else{
            alertBox.classList.add("success");
        }
        setTimeout(() => {
            alertBox.classList.remove("show");
        }, 3200);

    }
</script>

<?php if (isset($_GET['status'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const status = "<?= $_GET['status'] ?>";

            const messages = {
                success: "Profile updated successfully",
                wrong_password: "Current password is incorrect",
                password_mismatch: "Passwords do not match"
            };

            if (messages[status]) {
                showAlert(messages[status], status);
            }
        });
    </script>
<?php endif; ?>

</body>

</html>
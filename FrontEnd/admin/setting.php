<?php
$pageTitle = 'Profile Settings';
$pageSubtitle = 'Manage your admin account, security, and preferences.';
include("partials/nav.php");

$adminImg = $admininfo['image'] ?? '';
$adminImgPath = $adminImg && file_exists(__DIR__ . '/../assets/customer_profiles/' . $adminImg)
    ? '../assets/customer_profiles/' . $adminImg
    : '';
?>

<section class="section active">
    <div id="alertBox" class="alert"></div>

    <form action="utils/adminSettingChanges.php" method="post" enctype="multipart/form-data" id="profileForm">
        <input type="hidden" name="savebutton" value="1">
        <div class="section-actions" style="justify-content: flex-end; margin-bottom: 10px;">
            <a href="setting.php" class="btn-ghost btn">Cancel</a>
            <button type="submit" name="savebutton" value="1" class="btn-primary btn">Save changes</button>
        </div>

        <div class="settings-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Profile & photo -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Profile picture</div>
                        <div class="card-value" style="font-size: 0.929rem; font-weight: 400; color: var(--muted);">JPG, PNG, GIF or WebP — max 2 MB</div>
                    </div>
                    </div>
                    <div class="profile-photo-section">
                    <input type="file" name="profile_image" id="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" class="profile-photo-input">
                    <label for="profile_image" class="profile-photo-zone" id="profilePhotoZone">
                        <span class="profile-photo-preview" id="profilePreviewWrap">
                            <?php if ($adminImgPath): ?>
                                <img id="profilePreview" src="<?= htmlspecialchars($adminImgPath) ?>" alt="Profile" class="profile-photo-img">
                            <?php else: ?>
                                <span class="profile-photo-placeholder" id="profilePreview">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    <span class="profile-photo-text">Upload photo</span>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="profile-photo-btn">Change photo</span>
                    </label>
                </div>
            </div>

            <!-- Account info -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Account information</div>
                        <div class="card-value" style="font-size: 1.143rem;"><?= htmlspecialchars($name) ?></div>
                    </div>
                    <span class="card-chip">Administrator</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; font-size: 0.929rem;">
                    <div>
                        <label style="display: block; margin-bottom: 4px; color: var(--muted);">Full name</label>
                        <input autocomplete="off" name="fullname" type="text" value="<?= htmlspecialchars($name) ?>" required
                            class="settings-input" placeholder="Your name">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; color: var(--muted);">Email</label>
                        <input autocomplete="off" name="email" type="email" value="<?= htmlspecialchars($admininfo['email'] ?? '') ?>" required
                            class="settings-input" placeholder="admin@example.com">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; color: var(--muted);">Username</label>
                        <input autocomplete="off" name="username" type="text" value="<?= htmlspecialchars($admininfo['username'] ?? '') ?>" required
                            class="settings-input" placeholder="Login username">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; color: var(--muted);">Contact number</label>
                        <input autocomplete="off" name="phone" type="text" value="<?= htmlspecialchars($admininfo['phone'] ?? '') ?>"
                            class="settings-input" placeholder="+1 234 567 8900">
                    </div>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <div>
                    <div class="card-title">Change password</div>
                    <div class="card-value" style="font-size: 0.929rem; font-weight: 400; color: var(--muted);">Leave blank to keep current password. New password must be at least 8 characters.</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; font-size: 0.929rem; max-width: 560px;">
                <div>
                    <label style="display: block; margin-bottom: 4px; color: var(--muted);">Current password</label>
                    <input autocomplete="off" name="current_password" type="password" id="current_password" placeholder="••••••••"
                        class="settings-input">
                </div>
                <div></div>
                <div>
                    <label style="display: block; margin-bottom: 4px; color: var(--muted);">New password</label>
                    <input autocomplete="new-password" name="new_password" type="password" id="new_password" placeholder="Min 8 characters"
                        class="settings-input" minlength="8">
                    <span id="pwdHint" style="font-size: 0.786rem; color: var(--muted); display: block; margin-top: 4px;"></span>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 4px; color: var(--muted);">Confirm new password</label>
                    <input autocomplete="new-password" name="confirm_password" type="password" id="confirm_password" placeholder="Confirm new password"
                        class="settings-input">
                    <span id="confirmHint" style="font-size: 0.786rem; color: var(--danger); display: none; margin-top: 4px;">Passwords do not match</span>
                </div>
            </div>
        </div>
    </form>

    <!-- Preferences -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Preferences</div>
                <div class="card-value" style="font-size: 0.929rem; font-weight: 400; color: var(--muted);">Saved in your browser — changes apply instantly</div>
            </div>
        </div>
        <div style="margin-top: 16px;">
            <!-- Font style -->
            <div class="pref-section">
                <div class="pref-section-title">Font style</div>
                <div class="font-cards">
                    <div class="font-card selected" data-font="inter" style="font-family:'Inter',sans-serif">
                        <div class="font-card-preview" style="font-family: Inter;">Aa</div>
                        <div class="font-card-name">Inter</div>
                    </div>
                    <div class="font-card" data-font="roboto" style="font-family:'Roboto',sans-serif">
                        <div class="font-card-preview" style="font-family: Roboto;">Aa</div>
                        <div class="font-card-name">Roboto</div>
                    </div>
                    <div class="font-card" data-font="dmsans" style="font-family:'DM Sans',sans-serif">
                        <div class="font-card-preview"  style="font-family: DM Sans;">Aa</div>
                        <div class="font-card-name">DM Sans</div>
                    </div>
                    <div class="font-card" data-font="opensans" style="font-family:'Open Sans',sans-serif">
                        <div class="font-card-preview"  style="font-family: Open Sans;">Aa</div>
                        <div class="font-card-name">Open Sans</div>
                    </div>
                    <div class="font-card" data-font="poppins" style="font-family:'Poppins',sans-serif">
                        <div class="font-card-preview"  style="font-family: Poppins;">Aa</div>
                        <div class="font-card-name">Poppins</div>
                    </div>
                    <div class="font-card" data-font="sourcesans" style="font-family:'Source Sans 3',sans-serif">
                        <div class="font-card-preview" style="font-family: Source Sans 3;">Aa</div>
                        <div class="font-card-name">Source Sans</div>
                    </div>
                    <div class="font-card" data-font="system" style="font-family:system-ui,sans-serif">
                        <div class="font-card-preview"  style="font-family: system-ui;">Aa</div>
                        <div class="font-card-name">System</div>
                    </div>
                    <div class="font-card" data-font="georgia" style="font-family:Georgia,serif">
                        <div class="font-card-preview"  style="font-family: Georgia;">Aa</div>
                        <div class="font-card-name">Georgia</div>
                    </div>
                </div>
                <script>!function(){var f=localStorage.getItem('admin_font')||'inter';document.querySelectorAll('.font-card').forEach(function(c){c.classList.toggle('selected',c.getAttribute('data-font')===f);});}();</script>
            </div>

            <!-- Font size -->
            <div class="pref-section">
                <div class="pref-section-title">Font size</div>
                <div class="font-size-row">
                    <span style="font-size:0.786rem;color:var(--muted);">Small</span>
                    <input type="range" id="font-size-slider" class="font-size-slider" min="12" max="18" value="14" step="1">
                    <span style="font-size:0.786rem;color:var(--muted);">Large</span>
                    <span class="font-size-value" id="font-size-value">14</span>
                    <span style="font-size:0.786rem;color:var(--muted);">px</span>
                </div>
                <script>!function(){var s=parseInt(localStorage.getItem('admin_font_size'),10)||14;var sl=document.getElementById('font-size-slider');var ve=document.getElementById('font-size-value');if(sl){sl.value=s;}if(ve){ve.textContent=s;}}();</script>
            </div>

            <!-- Other toggles -->
            <div class="pref-section" style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
                <div class="pref-section-title">Dashboard & notifications</div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                        <label class="switch">
                            <input type="checkbox" id="pref-quick-stats" aria-label="Show quick stats">
                            <span class="slider"></span>
                        </label>
                        <span>Show quick stats on dashboard</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                        <label class="switch">
                            <input type="checkbox" id="pref-remember-filter" aria-label="Remember filter">
                            <span class="slider"></span>
                        </label>
                        <span>Remember year/month filter on dashboard</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                        <label class="switch">
                            <input type="checkbox" id="pref-notification-sound" aria-label="Notification sound">
                            <span class="slider"></span>
                        </label>
                        <span>Notification sound when opening pending registrations</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.settings-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid rgba(148,163,184,0.4);
    background: rgba(15,23,42,0.9);
    color: var(--text);
    font-size: 0.929rem;
}
.settings-input:focus {
    outline: none;
    border-color: var(--accent);
}
.profile-photo-input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}
.profile-photo-section {
    margin-top: 16px;
}
.profile-photo-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 24px;
    border: 2px dashed rgba(148,163,184,0.3);
    border-radius: 16px;
    background: rgba(15,23,42,0.5);
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.profile-photo-zone:hover {
    border-color: var(--accent);
    background: rgba(59,130,246,0.08);
}
.profile-photo-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-light);
    border: 2px solid var(--border);
}
.profile-photo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-photo-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--muted);
}
.profile-photo-placeholder svg {
    opacity: 0.6;
}
.profile-photo-text {
    font-size: 0.857rem;
}
.profile-photo-btn {
    font-size: 0.929rem;
    font-weight: 500;
    color: var(--accent);
}
@media (max-width: 768px) {
    .settings-grid { grid-template-columns: 1fr !important; }
}
</style>

<script src="script.js"></script>
<script>
(function() {
    var STORAGE_KEYS = {
        font: 'admin_font',
        fontSize: 'admin_font_size',
        quickStats: 'admin_quick_stats',
        rememberFilter: 'admin_remember_filter',
        notificationSound: 'admin_notification_sound'
    };
    var FONT_MAP = {
        inter: "'Inter',sans-serif",
        roboto: "'Roboto',sans-serif",
        dmsans: "'DM Sans',sans-serif",
        opensans: "'Open Sans',sans-serif",
        poppins: "'Poppins',sans-serif",
        sourcesans: "'Source Sans 3',sans-serif",
        system: "system-ui,-apple-system,sans-serif",
        georgia: "Georgia,serif"
    };
    function get(key, defaultVal) {
        var v = localStorage.getItem(key);
        return v === null ? defaultVal : v === '1';
    }
    function set(key, val) {
        localStorage.setItem(key, val ? '1' : '0');
    }
    function applyFont(f) {
        var stack = FONT_MAP[f] || FONT_MAP.inter;
        document.documentElement.style.setProperty('--font-family', stack);
        localStorage.setItem(STORAGE_KEYS.font, f);
    }
    function applyFontSize(s) {
        document.documentElement.style.setProperty('--font-size-base', s + 'px');
        localStorage.setItem(STORAGE_KEYS.fontSize, String(s));
    }
    document.addEventListener('DOMContentLoaded', function() {
        var font = localStorage.getItem(STORAGE_KEYS.font) || 'inter';
        var fontSize = parseInt(localStorage.getItem(STORAGE_KEYS.fontSize), 10) || 14;

        document.querySelectorAll('.font-card').forEach(function(c) {
            c.classList.toggle('selected', c.getAttribute('data-font') === font);
            c.addEventListener('click', function() {
                var f = this.getAttribute('data-font');
                document.querySelectorAll('.font-card').forEach(function(x) { x.classList.remove('selected'); });
                this.classList.add('selected');
                applyFont(f);
            });
        });

        var slider = document.getElementById('font-size-slider');
        var valueEl = document.getElementById('font-size-value');
        slider.value = fontSize;
        valueEl.textContent = fontSize;
        slider.addEventListener('input', function() {
            var s = parseInt(this.value, 10);
            valueEl.textContent = s;
            applyFontSize(s);
        });

        document.getElementById('pref-quick-stats').checked = get(STORAGE_KEYS.quickStats, true);
        document.getElementById('pref-remember-filter').checked = get(STORAGE_KEYS.rememberFilter, false);
        document.getElementById('pref-notification-sound').checked = get(STORAGE_KEYS.notificationSound, false);

        ['pref-quick-stats','pref-remember-filter','pref-notification-sound'].forEach(function(id, i) {
            var key = ['quickStats','rememberFilter','notificationSound'][i];
            document.getElementById(id).addEventListener('change', function() { set(STORAGE_KEYS[key], this.checked); });
        });
    });
})();

function showAlert(msg, status) {
    var alertBox = document.getElementById('alertBox');
    alertBox.classList.remove('error', 'success');
    alertBox.classList.add('show');
    alertBox.textContent = msg;
    alertBox.classList.add(status === 'wrong_password' || status === 'password_mismatch' || status === 'password_too_short' || status === 'password_required' || status === 'missing_fields' ? 'error' : 'success');
    setTimeout(function() { alertBox.classList.remove('show'); }, 4000);
}

document.getElementById('profile_image').addEventListener('change', function(e) {
    var f = e.target.files[0];
    var wrap = document.getElementById('profilePreviewWrap');
    if (!f || !f.type.match(/^image\//)) return;
    var r = new FileReader();
    r.onload = function() {
        var img = document.createElement('img');
        img.id = 'profilePreview';
        img.className = 'profile-photo-img';
        img.src = r.result;
        img.alt = 'Profile';
        wrap.innerHTML = '';
        wrap.appendChild(img);
    };
    r.readAsDataURL(f);
});

document.getElementById('new_password').addEventListener('input', function() {
    var len = this.value.length;
    var hint = document.getElementById('pwdHint');
    hint.textContent = len > 0 && len < 8 ? 'At least 8 characters required' : '';
});
document.getElementById('confirm_password').addEventListener('input', function() {
    var match = this.value === document.getElementById('new_password').value;
    document.getElementById('confirmHint').style.display = (this.value.length > 0 && !match) ? 'block' : 'none';
});

document.getElementById('profileForm').addEventListener('submit', function() {
    var newP = document.getElementById('new_password').value;
    var conf = document.getElementById('confirm_password').value;
    var cur = document.getElementById('current_password').value;
    if (newP !== '' || conf !== '' || cur !== '') {
        if (newP.length < 8 && newP !== '') {
            showAlert('New password must be at least 8 characters.', 'password_too_short');
            return false;
        }
        if (newP !== conf) {
            showAlert('New password and confirmation do not match.', 'password_mismatch');
            return false;
        }
    }
    return true;
});
</script>

<?php if (isset($_GET['status'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var messages = {
        success: 'Profile updated successfully.',
        wrong_password: 'Current password is incorrect.',
        password_mismatch: 'New password and confirmation do not match.',
        password_too_short: 'New password must be at least 8 characters.',
        password_required: 'Fill current password to change password.',
        missing_fields: 'Please fill in name, email and username.'
    };
    var msg = messages["<?= htmlspecialchars($_GET['status']) ?>"];
    if (msg) showAlert(msg, "<?= htmlspecialchars($_GET['status']) ?>");
});
</script>
<?php endif; ?>

</body>
</html>

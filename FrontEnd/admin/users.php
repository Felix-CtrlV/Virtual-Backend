<?php
$pageTitle = 'User Management';
$pageSubtitle = 'View and manage shopper accounts across the mall.';
include("partials/nav.php");
?>

<style>
    /* Layout Container */
    .view-container {
        display: flex;
        width: 100%;
        gap: 20px;
        position: relative;
        overflow-x: hidden; 
    }

    .table-wrapper {
        flex: 1;
        transition: all 0.4s ease;
        min-width: 0;
    }

    /* --- Side Pane Styles --- */
    .side-pane {
        border-radius: 14px;
        position: fixed;
        margin: 85px 0px;
        top: 0;
        right: -450px; /* Slightly wider for better spacing */
        width: 450px;
        height: 550px;
        background: #112042;
        border-left: 1px solid #1e293b;
        box-shadow: -10px 0 30px rgba(0,0,0,0.6);
        z-index: 900; 
        transition: right 0.4s cubic-bezier(0.19, 1, 0.22, 1); /* Smoother slide */
        padding: 30px 30px 30px 30px; 
        box-sizing: border-box;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .side-pane.open {
        right: 27px;
    }

    .pane-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 10px;
    }

    .close-btn {
        background: rgba(255,255,255,0.05);
        border: none;
        color: #94a3b8;
        width: 32px; height: 32px;
        border-radius: 50%;
        cursor: pointer;
        transition: 0.2s;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .close-btn:hover { background: rgba(255,255,255,0.1); color: white; }

    .pane-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .profile-img-lg {
        width: 130px; height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #1e293b;
        margin-bottom: 20px;
        background: #0f172a;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }

    .user-name { font-size: 1.75rem; font-weight: 700; margin-bottom: 5px; color: white; }
    .user-email { color: #94a3b8; font-size: 0.95rem; margin-bottom: 25px; }

    /* --- Status Toggle Group (New UI) --- */
    .status-group {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 15px;
        width: 100%;
    }

    .status-option {
        flex: 1;
        padding: 10px 5px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        text-align: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        opacity: 0.4; /* Dimmed by default */
        filter: grayscale(0.6);
        background: #1e293b;
        color: #cbd5e1;
    }

    .status-option:hover {
        opacity: 0.7;
        transform: translateY(-2px);
    }

    /* Active State for the Buttons (Selected) */
    .status-option.selected {
        opacity: 1;
        filter: grayscale(0);
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        border-color: rgba(255,255,255,0.1);
    }

    /* Specific Colors based on classes */
    .status-option.opt-active.selected { background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: #059669; }
    .status-option.opt-inactive.selected { background: rgba(100, 116, 139, 0.2); color: #94a3b8; border-color: #475569; }
    .status-option.opt-banned.selected { background: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #b91c1c; }

    /* Non-selected hover colors */
    .status-option.opt-active:hover { color: #34d399; }
    .status-option.opt-banned:hover { color: #f87171; }

    /* --- Loading Spinner --- */
    .loader-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
        width: 100%;
    }
    .spinner {
        width: 40px; height: 40px;
        border: 3px solid rgba(255,255,255,0.1);
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-top: 210px;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.7); z-index: 1000;
        justify-content: center; align-items: center;
        backdrop-filter: blur(2px);
    }
    .modal-overlay.show { display: flex; }
    .modal-box { background: #1e293b; padding: 25px; border-radius: 12px; width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid #334155; }
    .form-group { margin-bottom: 15px; text-align: left; }
    .form-group label { display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px; }
    .form-group textarea, .form-group input { 
        width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; 
        color: white; border-radius: 6px; box-sizing: border-box; 
    }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
</style>

<section class="section active">
    
    <div id="banModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-bottom: 15px; color: white;">Ban User</h3>
            <input type="hidden" id="banUserId">
            <input type="hidden" id="banUserRole">

            <div class="form-group">
                <label>Reason for Banning</label>
                <textarea id="banReason" rows="3" placeholder="Violation of terms..." style="resize: none;"></textarea>
            </div>

            <div class="form-group">
                <label>Banned Until</label>
                <input type="date" id="banDate">
            </div>

            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeBanModal()">Cancel</button>
                <button class="btn btn-primary" onclick="confirmBan()">Confirm Ban</button>
            </div>
        </div>
    </div>

    <div class="view-container">
        
        <div class="table-wrapper" id="tableWrapper">
            <div class="card">
                <div class="search">
                    <input autocomplete="off" type="text" id="searchuser" placeholder="Search Users..." onkeyup="fetchUsers(this.value)" />
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody id="userbody"></tbody>
                </table>
            </div>
        </div>

    </div> 
</section>

<div id="sidePane" class="side-pane">
    <div class="pane-header">
        <button class="close-btn" onclick="closeSidePane()">&times;</button>
    </div>
    
    <div id="paneLoading" class="loader-container" style="display: none;">
        <div class="spinner"></div>
    </div>
    
    <div id="paneContent" class="pane-content" style="display: none;">
        <img id="paneImage" src="" alt="Profile" class="profile-img-lg">
        
        <div id="paneName" class="user-name"></div>
        <div id="paneEmail" class="user-email"></div>
        
        <div style="width: 100%; border-top: 1px solid #334155; margin: 20px 0;"></div>

        <div style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
            Set Account Status
        </div>
        
        <div class="status-group">
            <div id="opt-active" class="status-option opt-active" onclick="handlePaneStatusClick('active')">
                Active
            </div>
            <div id="opt-inactive" class="status-option opt-inactive" onclick="handlePaneStatusClick('inactive')">
                Inactive
            </div>
            <div id="opt-banned" class="status-option opt-banned" onclick="handlePaneStatusClick('banned')">
                Banned
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 0.8rem; color: #64748b;">
            Joined on <span id="paneJoined"></span>
        </div>
    </div>
</div>

<script>
    // State variables for the currently open user
    let currentUser = { id: null, role: null };

    // 1. Fetch Table
    function fetchUsers(query = "") {
        fetch("utils/search_users.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "search=" + encodeURIComponent(query)
        })
        .then(res => res.text())
        .then(data => {
            document.getElementById("userbody").innerHTML = data;
        });
    }

    // 2. Open Side Pane
    function openUserPane(id, role) {
        const pane = document.getElementById('sidePane');
        const loading = document.getElementById('paneLoading');
        const content = document.getElementById('paneContent');
        
        // Show pane
        pane.classList.add('open');
        
        // Reset UI to loading state
        loading.style.display = 'flex';
        content.style.display = 'none';
        
        // Reset Selection styling
        document.querySelectorAll('.status-option').forEach(el => el.classList.remove('selected'));

        // Fetch Details
        const formData = new FormData();
        formData.append('id', id);
        formData.append('role', role);

        fetch('utils/get_user_details.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                currentUser = { id: data.id, role: data.role };
                
                // Populate Data
                document.getElementById('paneName').innerText = data.name;
                document.getElementById('paneEmail').innerText = data.email;
                document.getElementById('paneJoined').innerText = data.joined;
                
                const imgPath = data.image ? `../assets/customer_profiles/${data.image}` : '../assets/images/default-user.png'; 
                document.getElementById('paneImage').src = imgPath;
                
                // Highlight the correct status button
                highlightStatusOption(data.status);
                
                loading.style.display = 'none';
                content.style.display = 'flex'; // Use flex to maintain column layout
            }
        });
    }

    function closeSidePane() {
        document.getElementById('sidePane').classList.remove('open');
    }

    // 3. Handle Click Outside Logic
    document.addEventListener('click', function(event) {
        const pane = document.getElementById('sidePane');
        // If pane is open...
        if (pane.classList.contains('open')) {
            // AND click is NOT inside the pane
            // AND click is NOT on a table row (which opens the pane)
            const isClickInside = pane.contains(event.target);
            const isRowClick = event.target.closest('tr'); 
            
            if (!isClickInside && !isRowClick) {
                closeSidePane();
            }
        }
    });

    // 4. Handle Status Clicks
    function handlePaneStatusClick(newStatus) {
        // Optimistic UI update (highlight immediately)
        highlightStatusOption(newStatus);

        if (newStatus === 'banned') {
            openBanModal(currentUser.id, currentUser.role);
        } else {
            updateUserStatus(currentUser.id, currentUser.role, newStatus);
        }
    }

    // Helper to toggle CSS classes on the 3 buttons
    function highlightStatusOption(status) {
        // Remove selected class from all
        document.querySelectorAll('.status-option').forEach(el => el.classList.remove('selected'));
        
        // Add selected class to target
        const targetId = 'opt-' + status;
        const targetEl = document.getElementById(targetId);
        if(targetEl) {
            targetEl.classList.add('selected');
        }
    }

    // 5. Update Status (Active/Inactive)
    function updateUserStatus(id, role, newStatus) {
        fetch('utils/update_user_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, role, status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateTableRowUI(role, id, newStatus);
            } else {
                alert("Error: " + data.message);
                // Revert UI if error (optional, logic skipped for brevity)
            }
        });
    }

    // 6. Ban Logic
    function openBanModal(id, role) {
        document.getElementById('banUserId').value = id;
        document.getElementById('banUserRole').value = role;
        document.getElementById('banReason').value = ''; 
        document.getElementById('banDate').value = ''; 
        document.getElementById('banModal').classList.add('show');
    }

    function closeBanModal() {
        document.getElementById('banModal').classList.remove('show');
        // If they cancelled ban, we might want to revert the highlighted pill 
        // back to the actual database status. 
        // For now, we'll just leave it or fetch details again.
        openUserPane(currentUser.id, currentUser.role); 
    }

    function confirmBan() {
        const id = document.getElementById('banUserId').value;
        const role = document.getElementById('banUserRole').value;
        const reason = document.getElementById('banReason').value;
        const banned_until = document.getElementById('banDate').value;

        if (!reason || !banned_until) {
            alert("Please fill in all fields");
            return;
        }

        fetch('utils/handle_ban.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, role, reason, banned_until })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('banModal').classList.remove('show');
                highlightStatusOption('banned');
                updateTableRowUI(role, id, 'banned');
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    // 7. Helpers
    function updateTableRowUI(role, id, status) {
        const tablePill = document.getElementById(`table-status-${role}-${id}`);
        if(tablePill) {
            // Remove old status classes
            tablePill.className = tablePill.className.replace(/status-\w+/g, "");
            // Add new class and text
            tablePill.classList.add(`status-${status}`);
            tablePill.innerText = status.charAt(0).toUpperCase() + status.slice(1);
        }
    }

    // Initial load
    fetchUsers();
</script>
</body>
</html>
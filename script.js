    const ZONES = ['ET', 'MT', 'HM']; 

    // NOTE: Rate is 2.00. You might want to increase this for Rupees (e.g., 50 or 100)
    const RATE_PER_HOUR = 50.00; 
    
    let parkingData = {}; // Local cache of current zone
    let activityLog = [];
    let currentZone = 'ET';
    let selectedSpotId = null;
    let totalRevenue = 0;
    let currentUser = null;

    // ------------------ AUTH FUNCTIONS ------------------
    
    function switchAuthTab(tab) {
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        const tabLogin = document.getElementById('tabLogin');
        const tabSignup = document.getElementById('tabSignup');

        if (tab === 'login') {
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
            tabLogin.classList.add('active');
            tabSignup.classList.remove('active');
        } else {
            loginForm.classList.add('hidden');
            signupForm.classList.remove('hidden');
            tabLogin.classList.remove('active');
            tabSignup.classList.add('active');
        }
    }

    async function handleSignup(e) {
        e.preventDefault();
        const name = document.getElementById('signupName').value;
        const email = document.getElementById('signupEmail').value;
        const pass = document.getElementById('signupPassword').value;
        const conf = document.getElementById('signupConfirm').value;

        if (pass !== conf) {
            showToast("Passwords do not match", "error");
            return;
        }

        const formData = new FormData();
        formData.append('action', 'signup');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', pass);
        formData.append('confirm', conf);

        try {
            const res = await fetch('auth.php', { method: 'POST', body: formData });
            const data = await res.json();
            if(data.success) {
                showToast(data.message, "success");
                switchAuthTab('login');
            } else {
                showToast(data.message, "error");
            }
        } catch (err) { showToast("Connection error", "error"); }
    }

    async function handleLogin(e) {
        if(e) e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const pass = document.getElementById('loginPassword').value;

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('email', email);
        formData.append('password', pass);

        try {
            const res = await fetch('auth.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                currentUser = data.user;
                initApp();
                showToast(`Welcome back, ${currentUser.name}!`, "success");
            } else {
                showToast(data.message || "Login failed", "error");
            }
        } catch (err) { showToast("Connection error", "error"); }
    }

    async function handleLogout() {
        const formData = new FormData();
        formData.append('action', 'logout');
        await fetch('auth.php', { method: 'POST', body: formData });
        
        currentUser = null;
        document.getElementById('authScreen').classList.remove('hidden');
        document.body.classList.remove('app-active');
        showToast("Logged out", "info");
    }

    // ------------------ INITIALIZATION ------------------
    
    async function init() {
        try {
            const res = await fetch('auth.php?action=check_session');
            const data = await res.json();
            if (data.logged_in) {
                currentUser = data.user;
                initApp();
            } else {
                document.getElementById('authScreen').classList.remove('hidden');
                document.body.classList.remove('app-active');
            }
        } catch (err) {
            console.error("Backend connection failed. Did you start XAMPP?");
        }
    }

    async function initApp() {
        document.getElementById('authScreen').classList.add('hidden');
        document.body.classList.add('app-active');
        document.getElementById('currentUserName').innerText = currentUser.name.split(' ')[0];

        await renderZone(currentZone);
        await updateStats();
        await renderLogs();
    }

    // --- Rendering ---
    async function renderZone(zone) {
    currentZone = zone;
    
    // Map short IDs to Full Names
    const zoneNames = {
        'ET': 'ET Block',
        'MT': 'MT Block',
        'HM': 'HM Block'
    };
    
    // Update the Header Text
    document.getElementById('currentZoneLabel').innerText = zoneNames[zone] || zone;
    
    // Update Active Buttons
    document.querySelectorAll('.zone-btn').forEach(btn => {
        // Checks if the button text contains the zone code (e.g., 'ET')
        btn.classList.toggle('active', btn.innerText.includes(zone));
    });

    // ... rest of the function remains the same ...

    // ... rest of the function remains the same
        try {
            const res = await fetch(`api.php?action=get_status&zone=${zone}`);
            const result = await res.json();

            const container = document.getElementById('parkingLot');
            container.innerHTML = '';

            if(result.success) {
                parkingData[zone] = result.data; // Cache data

                result.data.forEach(spot => {
                    const el = document.createElement('div');
                    el.className = `parking-spot ${spot.status}`;
                    el.id = `spot-${spot.spot_id}`;
                    el.onclick = () => handleSpotClick(spot.spot_id);
                    
                    let icon = '<i class="fas fa-square"></i>';
                    let timerHtml = '';

                    if (spot.status === 'occupied') {
                        icon = '<i class="fas fa-car"></i>';
                        const duration = getParkingDuration(spot.entry_time);
                        timerHtml = `<span class="spot-timer">${duration}</span>`;
                    } else if (spot.status === 'reserved') {
                        icon = '<i class="fas fa-hand-paper"></i>';
                    }

                    el.innerHTML = `
                        <div class="car-icon">${icon}</div>
                        <div class="spot-id">${spot.spot_id}</div>
                        ${timerHtml}
                    `;
                    container.appendChild(el);
                });
            }
        } catch (err) { console.error(err); }
    }

    async function switchZone(zone) {
    cancelAction();
    await renderZone(zone);
    await updateStats(); // <--- THIS LINE FIXES IT
    }

    // --- Interactions ---
    async function handleSpotClick(id) {
        clearSelectionStyles();
        selectedSpotId = id;

        // Find spot data in local cache
        const spot = parkingData[currentZone].find(s => s.spot_id === id);

        document.getElementById('entryPanel').classList.add('hidden');
        document.getElementById('exitPanel').classList.add('hidden');
        document.getElementById('reservePanel').classList.add('hidden');
        
        document.getElementById(`spot-${id}`).classList.add('selected');

        if (spot.status === 'available') {
            document.getElementById('entryPanel').classList.remove('hidden');
            document.getElementById('selectedSpotDisplay').value = id;
            document.getElementById('entryPlate').value = '';
            document.getElementById('entryPlate').focus();
            checkButtons();
        } else if (spot.status === 'occupied') {
            document.getElementById('exitPanel').classList.remove('hidden');
            document.getElementById('exitSpotId').innerText = id;
            
            const duration = getParkingDuration(spot.entry_time);
            document.getElementById('exitVehicleInfo').innerHTML = `<strong>Plate:</strong> ${spot.vehicle_plate}`;
            document.getElementById('exitTimeInfo').innerHTML = `Parked for: ${duration}`;
            
        } else if (spot.status === 'reserved') {
            document.getElementById('reservePanel').classList.remove('hidden');
            document.getElementById('reserveSpotId').innerText = id;
        }
    }

    function clearSelectionStyles() {
        document.querySelectorAll('.parking-spot').forEach(el => {
            el.classList.remove('selected', 'found');
        });
    }

    function cancelAction() {
        document.getElementById('entryPanel').classList.remove('hidden');
        document.getElementById('exitPanel').classList.add('hidden');
        document.getElementById('reservePanel').classList.add('hidden');
        document.getElementById('selectedSpotDisplay').value = '';
        clearSelectionStyles();
        selectedSpotId = null;
        checkButtons();
        document.getElementById('searchResult').style.display = 'none';
    }

    // --- Core Logic ---

    async function parkVehicle() {
        if (!selectedSpotId) return;
        const plate = document.getElementById('entryPlate').value.toUpperCase().trim();
        if (!plate || plate.length < 2) {
            showToast("Please enter a valid license plate", "error");
            return;
        }

        const formData = new FormData();
        formData.append('action', 'park');
        formData.append('spot_id', selectedSpotId);
        formData.append('plate', plate);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            await renderZone(currentZone);
            await updateStats();
            await renderLogs();
            cancelAction();
        } else {
            showToast(data.message, 'error');
        }
    }

    async function reserveSpot() {
        if (!selectedSpotId) return;
        const formData = new FormData();
        formData.append('action', 'reserve');
        formData.append('spot_id', selectedSpotId);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.success) {
            showToast(data.message, 'info');
            await renderZone(currentZone);
            cancelAction();
        }
    }

    async function cancelReservation() {
        if (!selectedSpotId) return;
        const formData = new FormData();
        formData.append('action', 'cancel_reservation');
        formData.append('spot_id', selectedSpotId);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.success) {
            showToast(data.message, 'info');
            await renderZone(currentZone);
            cancelAction();
        }
    }

    async function calculateExit() {
    if (!selectedSpotId) return;
    
    const formData = new FormData();
    // CHANGE ACTION HERE
    formData.append('action', 'calculate_cost'); 
    formData.append('spot_id', selectedSpotId);

    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();

    if(data.success) {
        document.getElementById('modalPrice').innerText = `₹${data.cost.toFixed(2)}`;
        document.getElementById('modalBreakdown').innerText = `Duration: ${data.duration}`;
        document.getElementById('billingModal').classList.add('active');
    } else {
        showToast(data.message, 'error');
    }
}

    async function confirmExit() {
    if (!selectedSpotId) return;

    // Disable button to prevent double clicks
    const btn = document.querySelector('#billingModal .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    const formData = new FormData();
    formData.append('action', 'exit'); // This actually removes the car
    formData.append('spot_id', selectedSpotId);

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if(data.success) {
            closeModal();
            showToast("Payment processed successfully.", 'success');
            await renderZone(currentZone);
            await updateStats();
            await renderLogs();
            cancelAction();
        } else {
            showToast(data.message || "Error processing payment", 'error');
            // Re-enable button if error
            btn.disabled = false;
            btn.innerHTML = 'Confirm Payment';
        }
    } catch (err) {
        showToast("Connection error", "error");
        btn.disabled = false;
        btn.innerHTML = 'Confirm Payment';
    }
}
    function closeModal() {
        document.getElementById('billingModal').classList.remove('active');
    }

    async function autoPark() {
        const spot = parkingData[currentZone].find(s => s.status === 'available');
        if (spot) {
            handleSpotClick(spot.spot_id);
            showToast(`Auto-selected spot ${spot.spot_id}`, 'info');
            document.getElementById(`spot-${spot.spot_id}`).scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            showToast(`Zone ${currentZone} is full!`, 'error');
        }
    }

    async function findVehicle() {
        const query = document.getElementById('searchPlate').value.toUpperCase().trim();
        const resultBox = document.getElementById('searchResult');
        
        if (!query) return;

        const res = await fetch(`api.php?action=find&plate=${query}`);
        const data = await res.json();

        if (data.success) {
            const spot = data.spot;
            if (spot.zone !== currentZone) await switchZone(spot.zone);
            
            clearSelectionStyles();
            const el = document.getElementById(`spot-${spot.spot_id}`);
            el.classList.add('found');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            handleSpotClick(spot.spot_id);

            resultBox.className = 'search-result success';
            resultBox.innerText = `Found in Zone ${spot.zone}, Spot ${spot.spot_id}`;
        } else {
            resultBox.className = 'search-result error';
            resultBox.innerText = data.message;
        }
    }

    function checkButtons() {
        const plate = document.getElementById('entryPlate').value;
        const parkBtn = document.getElementById('parkBtn');
        const reserveBtn = document.getElementById('reserveBtn');
        
        parkBtn.disabled = !selectedSpotId || plate.length < 2;
        reserveBtn.disabled = !selectedSpotId;
    }

    // --- Stats & Utilities ---

    async function updateStats() {
        const res = await fetch(`api.php?action=get_stats&zone=${currentZone}`);
        const data = await res.json();
        
        if(data.success) {
            const s = data.stats;
            const percent = Math.round((s.occupied / s.total) * 100) || 0;

            document.getElementById('totalSpots').innerText = s.total;
            document.getElementById('occupiedSpots').innerText = s.occupied;
            document.getElementById('availableSpots').innerText = s.available;
            // CHANGE: Updated $ to ₹
            document.getElementById('totalRevenue').innerText = `₹${s.revenue}`;
            document.getElementById('occupancyPercent').innerText = `${percent}%`;

            const bar = document.getElementById('occupancyBar');
            bar.style.width = `${percent}%`;
            bar.className = 'progress-bar-fill'; 
            if (percent > 85) bar.classList.add('high');
            else if (percent > 50) bar.classList.add('medium');
        }
    }

    async function renderLogs() {
        const res = await fetch('api.php?action=get_logs');
        const data = await res.json();
        
        const list = document.getElementById('logList');
        list.innerHTML = '';
        
        if(data.success) {
            data.logs.forEach(log => {
                const item = document.createElement('li');
                item.className = 'log-item';
                
                let iconClass = 'system';
                let icon = 'fa-info';
                
                if(log.type === 'in') { iconClass='in'; icon='fa-arrow-right'; }
                else if(log.type === 'out') { iconClass='out'; icon='fa-arrow-left'; }

                item.innerHTML = `
                    <div class="log-icon ${iconClass}"><i class="fas ${icon}"></i></div>
                    <div>
                        <div>${log.msg}</div>
                        <div style="font-size:0.75rem; color: var(--text-light)">${log.time}</div>
                    </div>
                `;
                list.appendChild(item);
            });
        }
    }

    function getParkingDuration(entryTimeStr) {
        if(!entryTimeStr) return "0m";
        const entry = new Date(entryTimeStr);
        const now = new Date();
        const diff = now - entry;
        
        const mins = Math.floor(diff / 60000);
        if (mins < 60) return `${mins}m`;
        
        const hrs = Math.floor(mins / 60);
        const remainMins = mins % 60;
        return `${hrs}h ${remainMins}m`;
    }

    function showToast(msg, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast';
        
        const colors = { success: 'var(--success-color)', error: 'var(--danger-color)', info: 'var(--primary-color)' };
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        
        toast.style.borderLeftColor = colors[type];
        toast.innerHTML = `<i class="fas ${icon}" style="color: ${colors[type]}"></i> <div>${msg}</div>`;
        
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

        function exportLogs() {
        // Simply redirect to the API which will return the CSV file
        window.location.href = 'api.php?action=export_logs';
    }

    function resetSystem() {
        // Note: This functionality should ideally call a PHP script to reset DB
        if(confirm("Reset clears data via browser only. To fully reset, truncate DB tables.")) {
            // For now, just reload
            location.reload();
        }
    }

    // --- Event Listeners ---
    document.getElementById('entryPlate').addEventListener('input', checkButtons);
    
    document.getElementById('themeToggle').addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const icon = document.querySelector('#themeToggle i');
        icon.className = document.body.classList.contains('dark-mode') ? 'fas fa-sun' : 'fas fa-moon';
    });

    // Init on load
    init();
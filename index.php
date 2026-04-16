<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCTE Group of Institutes - Parking Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem; 
        }
        .logo img {
            height: 40px; 
            width: auto;
            border-radius: 60px;
        }

        .auth-header .logo {
            flex-direction: column;
            text-align: center;
            font-size: 1.2rem;
        }
        .auth-header .logo img {
            height: 80px;
            margin-bottom: 5px;
        }
    
.zone-selector { 
    display: flex; 
    background: var(--bg-color); 
    padding: 5px; 
    border-radius: var(--radius-md); 
    gap: 4px;
}

.zone-btn { 
    background: transparent; 
    border: none; 
    padding: 0.6rem 1.2rem; 
    border-radius: 6px; 
    cursor: pointer; 
    color: var(--text-light); 
    font-weight: 600; 
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Smooth transition */
    position: relative;
    z-index: 1;
}

/* Hover Effect: Light background tint and color change */
.zone-btn:hover {
    background: rgba(37, 99, 235, 0.1); /* Light Blue Tint */
    color: var(--primary-color);
    transform: translateY(-1px); /* Subtle lift */
}

/* Click/Press Effect: Button shrinks slightly */
.zone-btn:active {
    transform: scale(0.95); 
    background: rgba(37, 99, 235, 0.2); /* Darker Tint */
}

/* Selected/Active State: Solid background with shadow */
.zone-btn.active { 
    background: var(--card-bg); 
    color: var(--primary-color); 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); 
    transform: translateY(0); /* Reset lift */
}

/* Prevent hover effect from changing background if it's already active */
.zone-btn.active:hover {
    background: var(--card-bg);
    transform: translateY(0); /* Keep it steady */
}
    </style>
</head>
<body>
    <!-- ------------------ AUTH SCREEN ------------------ -->
    <div id="authScreen">
        <div class="auth-container">
            <div class="auth-header">
                <div class="logo">
                    <img src="data:image/jpeg;base64,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" alt="PCTE Logo">
                    <span>PCTE Group of Institutes</span>
                </div>
                <p>Campus Parking Management System</p>
            </div>
            
            <div class="auth-tabs">
                <div class="auth-tab active" id="tabLogin" onclick="switchAuthTab('login')">Login</div>
                <div class="auth-tab" id="tabSignup" onclick="switchAuthTab('signup')">Sign Up</div>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form" onsubmit="handleLogin(event)">
                <div class="input-group">
                    <label>College Email</label>
                    <input type="email" class="form-control" id="loginEmail" placeholder="student@pcte.edu.in" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" class="form-control" id="loginPassword" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Sign In</button>
            </form>

            <!-- Signup Form -->
            <form id="signupForm" class="auth-form hidden" onsubmit="handleSignup(event)">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" id="signupName" placeholder="Student Name" required>
                </div>
                <div class="input-group">
                    <label>College Email</label>
                    <input type="email" class="form-control" id="signupEmail" placeholder="student@pcte.edu.in" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" class="form-control" id="signupPassword" placeholder="Create password" required>
                </div>
                 <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" class="form-control" id="signupConfirm" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</button>
            </form>
        </div>
    </div>

    <!-- ------------------ MAIN APP ------------------ -->
    
    <header>
        <div class="logo">
            <img src="data:image/jpeg;base64,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" alt="PCTE Logo">
            <span>PCTE Parking</span>
        </div>
        <div class="header-controls">
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <span id="currentUserName">Student</span>
            </div>
            <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
            <button class="logout-btn" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </header>

    <main>
        <!-- Left Column -->
        <section>
            <!-- Occupancy Meter -->
            <div class="occupancy-container">
                <div class="occupancy-header">
                   <span>Total Occupancy (Block: <span id="currentZoneLabel">ET Block</span>)</span>
                   <span id="occupancyPercent">0%</span>
            </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="occupancyBar"></div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-car"></i></div>
                    <div class="stat-info"><h4>Total Spots</h4><p id="totalSpots">0</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--success-color); background: rgba(16, 185, 129, 0.1);"><i class="fas fa-check"></i></div>
                    <div class="stat-info"><h4>Available</h4><p id="availableSpots">0</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--danger-color); background: rgba(239, 68, 68, 0.1);"><i class="fas fa-ban"></i></div>
                    <div class="stat-info"><h4>Occupied</h4><p id="occupiedSpots">0</p></div>
                </div>
                <!-- Revenue Stat (kept for admin purposes) -->
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--warning-color); background: rgba(245, 158, 11, 0.1);"><i class="fas fa-indian-rupee-sign"></i></div>
                    <div class="stat-info"><h4>Fines/Fees</h4><p id="totalRevenue">₹0</p></div>
                </div>
            </div>

            <!-- Visual Map -->
            <div class="parking-section">
                <div class="section-header">
                    <h2>Parking Map</h2>
                   <div class="zone-selector">
                    <button class="zone-btn active" onclick="switchZone('ET')">ET Block</button>
                    <button class="zone-btn" onclick="switchZone('MT')">MT Block</button>
                    <button class="zone-btn" onclick="switchZone('HM')">HM Block</button>
                </div>
                </div>

                <div class="legend">
                    <div class="legend-item"><div class="legend-color" style="background: var(--success-color);"></div> Available</div>
                    <div class="legend-item"><div class="legend-color" style="background: var(--danger-color);"></div> Occupied</div>
                    <div class="legend-item"><div class="legend-color" style="background: var(--purple-color);"></div> Reserved</div>
                    <div class="legend-item"><div class="legend-color" style="background: var(--primary-color);"></div> Selected</div>
                </div>

                <div class="parking-lot" id="parkingLot">
                    <!-- JS Generated -->
                </div>
            </div>
        </section>

        <!-- Right Column: Controls -->
        <aside>
            <div class="control-panel">
                <h3 class="panel-title">Management</h3>
                
                <!-- Search Feature -->
                <div class="input-group">
                    <label>Find Vehicle (Reg. No.)</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" class="form-control" id="searchPlate" placeholder="e.g. PB-10-1234">
                        <button class="btn btn-outline" style="width: auto; margin: 0;" onclick="findVehicle()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="searchResult" class="search-result"></div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

                <!-- Entry Form -->
                <div id="entryPanel">
                    <div class="input-group">
                        <label>Selected Spot</label>
                        <input type="text" class="form-control" id="selectedSpotDisplay" placeholder="Select or Auto-Park" readonly>
                    </div>
                    <div class="input-group">
                        <label>Vehicle Reg. No.</label>
                        <input type="text" class="form-control" id="entryPlate" placeholder="PB-10-XXXX">
                    </div>
                    
                    <button class="btn btn-primary" id="parkBtn" disabled onclick="parkVehicle()">
                        <i class="fas fa-parking"></i> Confirm Parking
                    </button>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <button class="btn btn-outline" onclick="autoPark()">
                            <i class="fas fa-magic"></i> Auto
                        </button>
                        <button class="btn btn-warning" id="reserveBtn" disabled onclick="reserveSpot()">
                            <i class="fas fa-bookmark"></i> Reserve
                        </button>
                    </div>
                </div>

                <!-- Exit Form -->
                <div id="exitPanel" class="hidden">
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <i class="fas fa-car-side" style="font-size: 3rem; color: var(--danger-color);"></i>
                    </div>
                    <div class="input-group">
                        <label>Spot: <strong id="exitSpotId"></strong></label>
                        <div id="exitVehicleInfo" style="background: var(--bg-color); padding: 0.5rem; border-radius: 4px; font-size: 0.9rem;"></div>
                        <div id="exitTimeInfo" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-light);"></div>
                    </div>
                    <button class="btn btn-danger" onclick="calculateExit()">
                        <i class="fas fa-calculator"></i> Calculate & Exit
                    </button>
                    <button class="btn btn-outline" onclick="cancelAction()">Cancel</button>
                </div>

                <!-- Reserved Panel -->
                <div id="reservePanel" class="hidden">
                     <div style="text-align: center; margin-bottom: 1rem;">
                        <i class="fas fa-hand-paper" style="font-size: 3rem; color: var(--purple-color);"></i>
                        <p style="margin-top: 0.5rem;">Spot <strong id="reserveSpotId"></strong> is reserved.</p>
                    </div>
                    <button class="btn btn-primary" onclick="cancelReservation()">Cancel Reservation</button>
                    <button class="btn btn-outline" onclick="cancelAction()">Back</button>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

                <h3 class="panel-title">Admin Tools</h3>
                <button class="btn btn-outline" onclick="exportLogs()">
                    <i class="fas fa-download"></i> Export Logs (CSV)
                </button>
                <button class="btn btn-outline" style="color: var(--danger-color); border-color: var(--danger-color);" onclick="resetSystem()">
                    <i class="fas fa-trash-alt"></i> Reset All Data
                </button>

                <!-- Activity Log -->
                <h3 class="panel-title" style="margin-top: 2rem;">Recent Activity</h3>
                <ul class="log-list" id="logList"></ul>
            </div>
        </aside>
    </main>

    <!-- Billing Modal -->
    <div id="billingModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-receipt" style="color: var(--success-color)"></i> Payment Summary</h3>
            <div class="price-display" id="modalPrice">₹0.00</div>
            <div class="price-breakdown" id="modalBreakdown">Duration: 0 mins</div>
            <p style="margin-bottom: 1rem; font-size: 0.9rem;">Collect payment and release spot.</p>
            <button class="btn btn-primary" onclick="confirmExit()">Confirm Payment</button>
            <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

<script src="script.js"></script>
</body>
</html>
// Modal functions
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'block';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

function openAddResellerModal() {
    document.getElementById('addResellerModal').style.display = 'block';
}

function closeAddResellerModal() {
    document.getElementById('addResellerModal').style.display = 'none';
}

function openEditCreditsModal(resellerId, currentCredits) {
    document.getElementById('edit_reseller_id').value = resellerId;
    document.getElementById('edit_credits').value = currentCredits;
    document.getElementById('editCreditsModal').style.display = 'block';
}

function closeEditCreditsModal() {
    document.getElementById('editCreditsModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
}

// Copy API key to clipboard
function copyApiKey() {
    const apiKeyInput = document.getElementById('apiKey');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show copied message
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = 'Copied!';
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.classList.remove('btn-success');
    }, 2000);
}

// Maintenance form handling
document.addEventListener('DOMContentLoaded', function() {
    const maintenanceForm = document.getElementById('maintenanceForm');
    if (maintenanceForm) {
        maintenanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('update_maintenance', 'true');
            
            fetch('../api/maintenance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Maintenance settings updated successfully!', 'success');
                } else {
                    showAlert('Error updating maintenance settings: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Network error: ' + error, 'error');
            });
        });
    }
});

// Utility functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.main-content').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Search functionality
function searchTable(tableId, searchId) {
    const input = document.getElementById(searchId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}



// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Enhanced JavaScript functions

// Toast notification system
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Enhanced form handling with AJAX
function handleFormSubmit(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;
        this.classList.add('loading');
        
        const formData = new FormData(this);
        
        fetch('../api/user_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
                // Close modal if exists
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
                // Reload page after success
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Network error: ' + error, 'error');
        })
        .finally(() => {
            // Reset loading state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            this.classList.remove('loading');
        });
    });
}

// Initialize all forms when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user management forms
    handleFormSubmit('addUserForm');
    handleFormSubmit('addResellerForm');
    handleFormSubmit('editCreditsForm');
    
    // Add search functionality
    const searchInput = document.getElementById('searchUsers');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTable('usersTable', 'searchUsers');
        });
    }
    
    // Add real-time credit calculation
    const daysInput = document.getElementById('days');
    const creditsInfo = document.getElementById('creditsInfo');
    if (daysInput && creditsInfo) {
        daysInput.addEventListener('input', function() {
            const days = parseInt(this.value) || 0;
            creditsInfo.textContent = `Credits required: ${days}`;
        });
    }
});

// Enhanced search with debouncing
let searchTimeout;
function enhancedSearch(tableId, searchId) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchTable(tableId, searchId);
    }, 300);
}

// Export data functionality
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Clean and escape data
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV file
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Bulk actions
function selectAllRows(tableId) {
    const table = document.getElementById(tableId);
    const checkboxes = table.querySelectorAll('input[type="checkbox"]');
    const selectAll = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Password strength indicator
function checkPasswordStrength(password) {
    const strength = {
        0: 'Very Weak',
        1: 'Weak',
        2: 'Medium',
        3: 'Strong',
        4: 'Very Strong'
    };
    
    let score = 0;
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
    if (password.match(/\d/)) score++;
    if (password.match(/[^a-zA-Z\d]/)) score++;
    
    return {
        score: score,
        text: strength[score]
    };
}

// Real-time password strength
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            let strengthIndicator = this.parentNode.querySelector('.password-strength');
            
            if (!strengthIndicator) {
                strengthIndicator = document.createElement('div');
                strengthIndicator.className = 'password-strength';
                this.parentNode.appendChild(strengthIndicator);
            }
            
            strengthIndicator.textContent = `Strength: ${strength.text}`;
            strengthIndicator.className = `password-strength strength-${strength.score}`;
        });
    });
});

// Auto-logout after inactivity
let inactivityTime = function() {
    let time;
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    
    function logout() {
        showToast('Session expired due to inactivity', 'error');
        setTimeout(() => {
            window.location.href = 'login.php?logout=1';
        }, 2000);
    }
    
    function resetTimer() {
        clearTimeout(time);
        time = setTimeout(logout, 1800000); // 30 minutes
    }
};

// Initialize auto-logout
inactivityTime();
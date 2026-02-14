<?php
// Customers Tab Content
?>
<!-- Customers Management Tab -->
<div id="customers" class="tab-content">
    <div class="section">
        <h2 data-translate="customers.title">👥 Customer Management</h2>
        
        <!-- Add Customer Form -->
        <div class="subsection">
            <h3 data-translate="customers.addNew">➕ Add New Customer</h3>
            <form id="addCustomerForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div class="form-group">
                        <label data-translate="customers.name"><span style="color: red;">*</span> Name:</label>
                        <input type="text" name="name" id="customerName" required
                            style="width: 80%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                    <div class="form-group">
                        <label data-translate="customers.phone">Phone:</label>
                        <input type="tel" name="phone" id="customerPhone"
                            style="width: 80%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label data-translate="customers.email">Email:</label>
                        <input type="email" name="email" id="customerEmail"
                            style="width: 80%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                    <div class="form-group">
                        <label data-translate="customers.address">Address:</label>
                        <textarea name="address" id="customerAddress" rows="2"
                            style="width: 80%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn" data-translate="customers.addCustomer">Add Customer</button>
                </div>
            </form>
        </div>

        <!-- Search and Filter -->
        <div class="subsection">
            <h3 data-translate="customers.search">🔍 Search Customers</h3>
            <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="text" id="customerSearch" placeholder="Search by name, phone, or email..."
                    style="flex: 1; min-width: 200px; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                <button onclick="searchCustomers()" class="btn" data-translate="common.search">Search</button>
                <button onclick="loadCustomers()" class="btn" data-translate="common.reset">Reset</button>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="subsection">
            <h3 data-translate="customers.list">📋 Customer List</h3>
            <div style="overflow-x: auto;">
                <table id="customersTable" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="customers.id">ID</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="customers.name">Name</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="customers.phone">Phone</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="customers.email">Email</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="customers.totalPurchases">Total Purchases</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;" data-translate="common.createdAt">Created</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;" data-translate="common.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <!-- Customers will be loaded here -->
                    </tbody>
                </table>
                <div id="noCustomersFound" style="text-align: center; padding: 40px; color: #666; display: none;">
                    <p data-translate="customers.noCustomersFound">No customers found</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 data-translate="customers.editCustomer">Edit Customer</h3>
            <span class="close" onclick="closeEditCustomerModal()">&times;</span>
        </div>
        <form id="editCustomerForm">
            <input type="hidden" id="editCustomerId" name="id">
            <div class="form-group">
                <label data-translate="customers.name"><span style="color: red;">*</span> Name:</label>
                <input type="text" name="name" id="editCustomerName" required
                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <div class="form-group">
                <label data-translate="customers.phone">Phone:</label>
                <input type="tel" name="phone" id="editCustomerPhone"
                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <div class="form-group">
                <label data-translate="customers.email">Email:</label>
                <input type="email" name="email" id="editCustomerEmail"
                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <div class="form-group">
                <label data-translate="customers.address">Address:</label>
                <textarea name="address" id="editCustomerAddress" rows="3"
                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
            </div>
            <div class="form-group">
                <label data-translate="customers.totalPurchases">Total Purchases:</label>
                <input type="number" name="total_purchases" id="editCustomerTotalPurchases" step="0.01" min="0"
                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeEditCustomerModal()" class="btn" style="background: #6c757d;" data-translate="common.cancel">Cancel</button>
                <button type="submit" class="btn" data-translate="customers.updateCustomer">Update Customer</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.btn {
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.btn:hover {
    background: #0056b3;
}

.btn-edit {
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    margin-right: 5px;
}

.btn-edit:hover {
    background: #1e7e34;
}

.btn-delete {
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.btn-delete:hover {
    background: #c82333;
}

.subsection {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}
</style>

<script>
let allCustomers = [];
let customersLoaded = false;

// Load customers when tab is shown
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customers tab script loaded');
    
    // Add event listener for when customers tab is shown
    const customersTab = document.querySelector('[onclick*="showTab(\'customers\')"]');
    console.log('Customers tab button found:', customersTab);
    
    if (customersTab) {
        customersTab.addEventListener('click', function() {
            console.log('Customers tab clicked');
            setTimeout(() => {
                if (!customersLoaded) {
                    loadCustomers();
                    customersLoaded = true;
                }
            }, 100);
        });
    } else {
        console.log('Customers tab button not found, trying alternative selector');
        // Try alternative selector
        const allTabs = document.querySelectorAll('.nav-tab');
        allTabs.forEach(tab => {
            if (tab.textContent.includes('Customers') || tab.getAttribute('data-translate') === 'navigation.customers') {
                console.log('Found customers tab with alternative method:', tab);
                tab.addEventListener('click', function() {
                    console.log('Customers tab clicked (alternative method)');
                    setTimeout(() => {
                        if (!customersLoaded) {
                            loadCustomers();
                            customersLoaded = true;
                        }
                    }, 100);
                });
            }
        });
    }
    
    // Handle add customer form submission
    const addForm = document.getElementById('addCustomerForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addCustomer();
        });
    }
    
    // Handle edit customer form submission
    const editForm = document.getElementById('editCustomerForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateCustomer();
        });
    }
    
    // Handle search input
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
    }
    
    // Also try to load customers immediately if the tab is already visible
    const customersTabContent = document.getElementById('customers');
    if (customersTabContent && customersTabContent.style.display !== 'none') {
        loadCustomers();
        customersLoaded = true;
    }
});

function loadCustomers() {
    console.log('Loading customers...');
    fetch('../api/customers.php')
        .then(response => response.json())
        .then(data => {
            console.log('Customers API response:', data);
            if (data.success) {
                allCustomers = data.data;
                displayCustomers(allCustomers);
            } else {
                console.error('Error loading customers:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    const noCustomersDiv = document.getElementById('noCustomersFound');
    
    if (customers.length === 0) {
        tbody.innerHTML = '';
        noCustomersDiv.style.display = 'block';
        return;
    }
    
    noCustomersDiv.style.display = 'none';
    
    tbody.innerHTML = customers.map(customer => `
        <tr style="border-bottom: 1px solid #dee2e6;">
            <td style="padding: 12px;">${customer.id}</td>
            <td style="padding: 12px; font-weight: bold;">${customer.name}</td>
            <td style="padding: 12px;">${customer.phone || '-'}</td>
            <td style="padding: 12px;">${customer.email || '-'}</td>
            <td style="padding: 12px;">${customer.total_purchases.toFixed(2)}</td>
            <td style="padding: 12px;">${new Date(customer.created_at).toLocaleDateString()}</td>
            <td style="padding: 12px; text-align: center;">
                <button class="btn-edit" onclick="editCustomer(${customer.id})" data-translate="common.edit">Edit</button>
                <button class="btn-delete" onclick="deleteCustomer(${customer.id})" data-translate="common.delete">Delete</button>
            </td>
        </tr>
    `).join('');
}

function addCustomer() {
    const formData = new FormData(document.getElementById('addCustomerForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value.trim() || null;
    }
    
    fetch('../api/customers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Customer added successfully!');
            document.getElementById('addCustomerForm').reset();
            loadCustomers();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding customer. Please try again.');
    });
}

function editCustomer(id) {
    const customer = allCustomers.find(c => c.id === id);
    if (!customer) return;
    
    document.getElementById('editCustomerId').value = customer.id;
    document.getElementById('editCustomerName').value = customer.name;
    document.getElementById('editCustomerPhone').value = customer.phone || '';
    document.getElementById('editCustomerEmail').value = customer.email || '';
    document.getElementById('editCustomerAddress').value = customer.address || '';
    document.getElementById('editCustomerTotalPurchases').value = customer.total_purchases;
    
    document.getElementById('editCustomerModal').style.display = 'block';
}

function closeEditCustomerModal() {
    document.getElementById('editCustomerModal').style.display = 'none';
}

function updateCustomer() {
    const formData = new FormData(document.getElementById('editCustomerForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value.trim() || null;
    }
    
    fetch(`../api/customers.php?id=${data.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Customer updated successfully!');
            closeEditCustomerModal();
            loadCustomers();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating customer. Please try again.');
    });
}

function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) {
        return;
    }
    
    fetch(`../api/customers.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Customer deleted successfully!');
            loadCustomers();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting customer. Please try again.');
    });
}

function searchCustomers() {
    const searchTerm = document.getElementById('customerSearch').value.toLowerCase().trim();
    
    if (!searchTerm) {
        displayCustomers(allCustomers);
        return;
    }
    
    const filteredCustomers = allCustomers.filter(customer => 
        customer.name.toLowerCase().includes(searchTerm) ||
        (customer.phone && customer.phone.toLowerCase().includes(searchTerm)) ||
        (customer.email && customer.email.toLowerCase().includes(searchTerm))
    );
    
    displayCustomers(filteredCustomers);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editCustomerModal');
    if (event.target === modal) {
        closeEditCustomerModal();
    }
}
</script>

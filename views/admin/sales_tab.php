<?php
// Sales Tab Content
?>
<!-- Sales Tab -->
<div id="sales" class="tab-content">
    <div class="section">
        <h2>💰 Sales Management</h2>
        <div id="sales-stats" class="stats-grid"></div>

        <!-- Search Bar -->
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
            <button onclick="startBarcodeScanner()" class="btn btn-primary" style="padding: 12px 20px; background: #28a745;">
                📷 Scan Barcode
            </button>
            <div style="flex: 1; max-width: 400px;">
                <input type="text" id="salesSearchInput" placeholder="🔍 Search by Receipt Number " data-translate-placeholder="sales.searchReceipt"
                    style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                    onkeyup="filterSales(this.value)"
                    onkeypress="if(event.key==='Enter') filterSales(this.value)">
            </div>
            <button onclick="clearSalesSearch()" class="btn btn-secondary"
                style="padding: 12px 20px;">Clear</button>
        </div>

        <!-- Search Results Count -->
        <div id="sales-search-results-count" style="margin-bottom: 15px; color: #666; font-size: 14px;">
            Showing all sales
        </div>

        <table>
            <thead>
                <tr>
                    <th data-translate="sales.receiptNumber">Receipt #</th>
                    <th data-translate="sales.date">Date</th>
                    <th data-translate="sales.staff">Staff</th>
                    <th data-translate="sales.customer">Customer</th>
                    <th data-translate="sales.totalAmount">Total Amount</th>
                    <th data-translate="sales.actions">Actions</th>
                </tr>
            </thead>
            <tbody id="sales-tbody"></tbody>
        </table>
    </div>
</div>

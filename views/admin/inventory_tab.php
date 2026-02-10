<?php
// Inventory Tab Content
?>
<!-- Inventory Tab -->
<div id="inventory" class="tab-content">
    <div class="section">
        <h2 data-translate="inventory.title">📋 Inventory Management</h2>
        <div id="inventory-stats" class="stats-grid"></div>

        <!-- Search Bar -->
        <div class="form-group" style="margin: 20px 0;">
            <label data-translate="inventory.search">🔍 Search Products:</label>
            <input type="text" id="inventory-search"
                placeholder="Search by code, brand, model, or description..."
                style="width: 100%; max-width: 500px; font-size: 16px; padding: 12px;">
            <div id="search-results-count" style="margin-top: 5px; color: #666; font-size: 14px;"></div>
        </div>

        <table id="inventory-table">
            <thead>
                <tr>
                    <th data-translate="inventory.code">Code</th>
                    <th data-translate="inventory.barcode">Barcode</th>
                    <th data-translate="inventory.product">Product</th>
                    <th data-translate="inventory.category">Category</th>
                    <th data-translate="inventory.color">Color</th>
                    <th data-translate="inventory.serial">Serial/IMEI</th>
                    <th data-translate="inventory.supplier">Supplier</th>
                    <th data-translate="inventory.purchasePrice">Purchase Price</th>
                    <th data-translate="inventory.minPrice">Min Price</th>
                    <th data-translate="inventory.suggestedPrice">Suggested Price</th>
                    <th data-translate="inventory.stock">Stock</th>
                    <th data-translate="inventory.status">Status</th>
                    <th data-translate="inventory.stockDetails">Stock Details</th>
                    <th data-translate="inventory.actions">Actions</th>
                </tr>
            </thead>
            <tbody id="inventory-tbody"></tbody>
        </table>
    </div>
</div>

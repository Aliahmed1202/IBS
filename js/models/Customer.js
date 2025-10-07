// Customer Model - Manages customer information and purchase history
class Customer {
    constructor(id, name, phone, email = '', address = '') {
        this.id = id;
        this.name = name;
        this.phone = phone;
        this.email = email;
        this.address = address;
        this.createdAt = new Date();
        this.totalPurchases = 0;
        this.lastPurchase = null;
    }

    // Static method to get all customers from localStorage
    static getAll() {
        const customers = localStorage.getItem('customers');
        return customers ? JSON.parse(customers).map(c => Object.assign(new Customer(), c)) : Customer.getDefaultCustomers();
    }

    // Static method to get default demo customers
    static getDefaultCustomers() {
        const defaultCustomers = [
            new Customer(1, 'John Smith', '555-0101', 'john.smith@email.com', '123 Main St, City'),
            new Customer(2, 'Sarah Johnson', '555-0102', 'sarah.j@email.com', '456 Oak Ave, City'),
            new Customer(3, 'Mike Davis', '555-0103', 'mike.davis@email.com', '789 Pine St, City'),
            new Customer(4, 'Emily Brown', '555-0104', 'emily.brown@email.com', '321 Elm St, City'),
            new Customer(5, 'David Wilson', '555-0105', 'david.w@email.com', '654 Maple Ave, City')
        ];
        
        // Set some demo purchase data
        defaultCustomers[0].totalPurchases = 1299.99;
        defaultCustomers[0].lastPurchase = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000); // 7 days ago
        defaultCustomers[1].totalPurchases = 899.99;
        defaultCustomers[1].lastPurchase = new Date(Date.now() - 3 * 24 * 60 * 60 * 1000); // 3 days ago
        
        Customer.saveAll(defaultCustomers);
        return defaultCustomers;
    }

    // Static method to save all customers to localStorage
    static saveAll(customers) {
        localStorage.setItem('customers', JSON.stringify(customers));
    }

    // Static method to find customer by ID
    static findById(id) {
        const customers = Customer.getAll();
        return customers.find(customer => customer.id === id);
    }

    // Static method to find customer by phone
    static findByPhone(phone) {
        const customers = Customer.getAll();
        return customers.find(customer => customer.phone === phone);
    }

    // Static method to search customers
    static search(query) {
        const customers = Customer.getAll();
        const searchTerm = query.toLowerCase();
        return customers.filter(customer => 
            customer.name.toLowerCase().includes(searchTerm) ||
            customer.phone.includes(searchTerm) ||
            customer.email.toLowerCase().includes(searchTerm)
        );
    }

    // Static method to create new customer
    static create(customerData) {
        const customers = Customer.getAll();
        const newId = Math.max(...customers.map(c => c.id), 0) + 1;
        
        const newCustomer = new Customer(
            newId,
            customerData.name,
            customerData.phone,
            customerData.email,
            customerData.address
        );

        customers.push(newCustomer);
        Customer.saveAll(customers);
        return newCustomer;
    }

    // Static method to update customer
    static update(id, customerData) {
        const customers = Customer.getAll();
        const customerIndex = customers.findIndex(customer => customer.id === id);
        
        if (customerIndex !== -1) {
            const updatedCustomer = {
                ...customers[customerIndex],
                ...customerData
            };
            customers[customerIndex] = updatedCustomer;
            Customer.saveAll(customers);
            return updatedCustomer;
        }
        return null;
    }

    // Static method to delete customer
    static delete(id) {
        const customers = Customer.getAll();
        const filteredCustomers = customers.filter(customer => customer.id !== id);
        Customer.saveAll(filteredCustomers);
        return filteredCustomers.length < customers.length;
    }

    // Static method to update purchase info
    static updatePurchaseInfo(id, amount) {
        const customer = Customer.findById(id);
        if (customer) {
            return Customer.update(id, {
                totalPurchases: customer.totalPurchases + amount,
                lastPurchase: new Date()
            });
        }
        return null;
    }

    // Static method to get top customers by purchase amount
    static getTopCustomers(limit = 10) {
        const customers = Customer.getAll();
        return customers
            .filter(customer => customer.totalPurchases > 0)
            .sort((a, b) => b.totalPurchases - a.totalPurchases)
            .slice(0, limit);
    }

    // Static method to get recent customers
    static getRecentCustomers(limit = 10) {
        const customers = Customer.getAll();
        return customers
            .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
            .slice(0, limit);
    }

    // Instance method to get formatted total purchases
    getFormattedTotalPurchases() {
        return `$${this.totalPurchases.toFixed(2)}`;
    }

    // Instance method to get formatted last purchase date
    getFormattedLastPurchase() {
        if (!this.lastPurchase) return 'Never';
        
        const date = new Date(this.lastPurchase);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
        if (diffDays < 365) return `${Math.ceil(diffDays / 30)} months ago`;
        return `${Math.ceil(diffDays / 365)} years ago`;
    }

    // Instance method to check if customer is new
    isNewCustomer() {
        return this.totalPurchases === 0;
    }

    // Instance method to check if customer is VIP (high value)
    isVIP(threshold = 1000) {
        return this.totalPurchases >= threshold;
    }

    // Instance method to get customer tier
    getTier() {
        if (this.totalPurchases >= 2000) return 'Platinum';
        if (this.totalPurchases >= 1000) return 'Gold';
        if (this.totalPurchases >= 500) return 'Silver';
        if (this.totalPurchases > 0) return 'Bronze';
        return 'New';
    }

    // Instance method to get contact info
    getContactInfo() {
        const contact = [this.phone];
        if (this.email) contact.push(this.email);
        return contact.join(' | ');
    }
}

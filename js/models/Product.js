// Product Model - Manages mobile phone inventory
class Product {
    constructor(id, brand, model, price, stock, image = '', description = '') {
        this.id = id;
        this.brand = brand;
        this.model = model;
        this.price = parseFloat(price);
        this.stock = parseInt(stock);
        this.image = image;
        this.description = description;
        this.createdAt = new Date();
        this.updatedAt = new Date();
    }

    // Static method to get all products from localStorage
    static getAll() {
        const products = localStorage.getItem('products');
        return products ? JSON.parse(products).map(p => Object.assign(new Product(), p)) : Product.getDefaultProducts();
    }

    // Static method to get default demo products
    static getDefaultProducts() {
        const defaultProducts = [
            new Product(1, 'Apple', 'iPhone 15 Pro', 999.99, 25, 'https://via.placeholder.com/300x300?text=iPhone+15+Pro', 'Latest iPhone with A17 Pro chip'),
            new Product(2, 'Samsung', 'Galaxy S24 Ultra', 899.99, 30, 'https://via.placeholder.com/300x300?text=Galaxy+S24', 'Premium Android flagship'),
            new Product(3, 'Google', 'Pixel 8 Pro', 699.99, 15, 'https://via.placeholder.com/300x300?text=Pixel+8+Pro', 'Pure Android experience'),
            new Product(4, 'OnePlus', 'OnePlus 12', 599.99, 20, 'https://via.placeholder.com/300x300?text=OnePlus+12', 'Flagship killer smartphone'),
            new Product(5, 'Xiaomi', 'Mi 14 Ultra', 549.99, 8, 'https://via.placeholder.com/300x300?text=Mi+14+Ultra', 'High-end camera phone')
        ];
        Product.saveAll(defaultProducts);
        return defaultProducts;
    }

    // Static method to save all products to localStorage
    static saveAll(products) {
        localStorage.setItem('products', JSON.stringify(products));
    }

    // Static method to find product by ID
    static findById(id) {
        const products = Product.getAll();
        return products.find(product => product.id === id);
    }

    // Static method to search products
    static search(query) {
        const products = Product.getAll();
        const searchTerm = query.toLowerCase();
        return products.filter(product => 
            product.brand.toLowerCase().includes(searchTerm) ||
            product.model.toLowerCase().includes(searchTerm) ||
            product.description.toLowerCase().includes(searchTerm)
        );
    }

    // Static method to filter products by brand
    static filterByBrand(brand) {
        const products = Product.getAll();
        return brand ? products.filter(product => product.brand === brand) : products;
    }

    // Static method to get all unique brands
    static getBrands() {
        const products = Product.getAll();
        return [...new Set(products.map(product => product.brand))].sort();
    }

    // Static method to get low stock products
    static getLowStock(threshold = 10) {
        const products = Product.getAll();
        return products.filter(product => product.stock <= threshold);
    }

    // Static method to create new product
    static create(productData) {
        const products = Product.getAll();
        const newId = Math.max(...products.map(p => p.id), 0) + 1;
        
        const newProduct = new Product(
            newId,
            productData.brand,
            productData.model,
            productData.price,
            productData.stock,
            productData.image,
            productData.description
        );

        products.push(newProduct);
        Product.saveAll(products);
        return newProduct;
    }

    // Static method to update product
    static update(id, productData) {
        const products = Product.getAll();
        const productIndex = products.findIndex(product => product.id === id);
        
        if (productIndex !== -1) {
            const updatedProduct = {
                ...products[productIndex],
                ...productData,
                updatedAt: new Date()
            };
            products[productIndex] = updatedProduct;
            Product.saveAll(products);
            return updatedProduct;
        }
        return null;
    }

    // Static method to delete product
    static delete(id) {
        const products = Product.getAll();
        const filteredProducts = products.filter(product => product.id !== id);
        Product.saveAll(filteredProducts);
        return filteredProducts.length < products.length;
    }

    // Static method to update stock
    static updateStock(id, quantity) {
        const product = Product.findById(id);
        if (product) {
            return Product.update(id, { stock: product.stock + quantity });
        }
        return null;
    }

    // Static method to reduce stock (for sales)
    static reduceStock(id, quantity) {
        const product = Product.findById(id);
        if (product && product.stock >= quantity) {
            return Product.update(id, { stock: product.stock - quantity });
        }
        return null;
    }

    // Instance method to get stock status
    getStockStatus() {
        if (this.stock === 0) return 'out-of-stock';
        if (this.stock <= 10) return 'low-stock';
        return 'in-stock';
    }

    // Instance method to get stock status text
    getStockStatusText() {
        const status = this.getStockStatus();
        switch (status) {
            case 'out-of-stock': return 'Out of Stock';
            case 'low-stock': return 'Low Stock';
            case 'in-stock': return 'In Stock';
            default: return 'Unknown';
        }
    }

    // Instance method to format price
    getFormattedPrice() {
        return `$${this.price.toFixed(2)}`;
    }

    // Instance method to get full name
    getFullName() {
        return `${this.brand} ${this.model}`;
    }

    // Instance method to check if product is available
    isAvailable(quantity = 1) {
        return this.stock >= quantity;
    }
}

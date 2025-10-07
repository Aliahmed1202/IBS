// User Model - Base class for authentication and user management
class User {
    constructor(id, username, password, role, name, phone = '', email = '') {
        this.id = id;
        this.username = username;
        this.password = password;
        this.role = role; // 'staff' or 'admin'
        this.name = name;
        this.phone = phone;
        this.email = email;
        this.createdAt = new Date();
        this.isActive = true;
    }

    // Static method to get all users from localStorage
    static getAll() {
        const users = localStorage.getItem('users');
        if (users) {
            // Convert plain objects back to User instances
            return JSON.parse(users).map(userData => {
                const user = new User(
                    userData.id,
                    userData.username,
                    userData.password,
                    userData.role,
                    userData.name,
                    userData.phone,
                    userData.email
                );
                user.createdAt = userData.createdAt;
                user.isActive = userData.isActive !== undefined ? userData.isActive : true;
                return user;
            });
        }
        return User.getDefaultUsers();
    }

    // Static method to get default demo users
    static getDefaultUsers() {
        const defaultUsers = [
            new User(1, 'admin', 'admin123', 'admin', 'System Admin', '123-456-7890', 'admin@mobileshop.com'),
            new User(2, 'staff', 'staff123', 'staff', 'Staff User', '123-456-7891', 'staff@mobileshop.com')
        ];
        User.saveAll(defaultUsers);
        return defaultUsers;
    }

    // Static method to save all users to localStorage
    static saveAll(users) {
        localStorage.setItem('users', JSON.stringify(users));
    }

    // Static method to authenticate user
    static authenticate(username, password, role) {
        const users = User.getAll();
        return users.find(user => 
            user.username === username && 
            user.password === password && 
            user.role === role &&
            user.isActive
        );
    }

    // Static method to find user by ID
    static findById(id) {
        const users = User.getAll();
        return users.find(user => user.id === id);
    }

    // Static method to find user by username
    static findByUsername(username) {
        const users = User.getAll();
        return users.find(user => user.username === username);
    }

    // Static method to create new user
    static create(userData) {
        const users = User.getAll();
        const newId = Math.max(...users.map(u => u.id), 0) + 1;
        
        const newUser = new User(
            newId,
            userData.username,
            userData.password,
            userData.role,
            userData.name,
            userData.phone,
            userData.email
        );

        users.push(newUser);
        User.saveAll(users);
        return newUser;
    }

    // Static method to update user
    static update(id, userData) {
        const users = User.getAll();
        const userIndex = users.findIndex(user => user.id === id);
        
        if (userIndex !== -1) {
            users[userIndex] = { ...users[userIndex], ...userData };
            User.saveAll(users);
            return users[userIndex];
        }
        return null;
    }

    // Static method to delete user
    static delete(id) {
        const users = User.getAll();
        const filteredUsers = users.filter(user => user.id !== id);
        User.saveAll(filteredUsers);
        return filteredUsers.length < users.length;
    }

    // Static method to deactivate user
    static deactivate(id) {
        return User.update(id, { isActive: false });
    }

    // Static method to activate user
    static activate(id) {
        return User.update(id, { isActive: true });
    }

    // Instance method to check permissions
    hasPermission(permission) {
        const permissions = {
            'admin': ['view_dashboard', 'manage_inventory', 'manage_sales', 'manage_customers', 'view_reports', 'manage_staff'],
            'staff': ['view_dashboard', 'manage_inventory', 'manage_sales', 'manage_customers']
        };

        return permissions[this.role] && permissions[this.role].includes(permission);
    }

    // Instance method to get user display name
    getDisplayName() {
        return this.name || this.username;
    }
}

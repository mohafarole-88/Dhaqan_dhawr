# Dhaqan Dhowr - Cultural Heritage Marketplace

## 🌍 Project Overview

**Dhaqan Dhowr** is a comprehensive web-based marketplace dedicated to preserving and promoting Somali cultural heritage through the trading of traditional tools, cultural foods, and heritage items. The platform connects buyers and sellers while serving as a digital repository for cultural preservation.

### 🎯 Mission Statement
To create a digital marketplace that facilitates the preservation, documentation, and trade of Somali traditional tools and cultural items, making them accessible to a global audience while supporting local artisans and cultural practitioners.

---

## ✨ Core Features

### 🔐 Multi-Role Authentication System
- **Buyer Role**: Browse, purchase, and review products
- **Seller Role**: Manage products, process orders, track sales
- **Admin Role**: Oversee entire platform, manage users and content

### 🛍️ E-Commerce Functionality
- Product catalog with categories and detailed descriptions
- Advanced search and filtering capabilities
- Shopping cart with quantity management
- Secure checkout process with multiple payment options
- Order tracking and management system

### 📊 Administrative Dashboard
- User management (buyers, sellers, admins)
- Product moderation and approval system
- Order oversight and analytics
- Contact form management through comments system
- Comprehensive reporting and analytics

### 💬 Communication System
- Contact form with admin notification system
- User messaging capabilities
- Product review and rating system
- Seller application process

---

## 🏗️ System Architecture

### Directory Structure
```
dhaqan_dhawr/
├── Auth/                    # Authentication system
│   ├── process_login.php
│   ├── process_register.php
│   └── logout.php
├── admin/                   # Admin dashboard
│   ├── index.php           # Dashboard overview
│   ├── users.php           # User management
│   ├── products.php        # Product management
│   ├── orders.php          # Order oversight
│   ├── comments.php        # Contact form management
│   ├── categories.php      # Category management
│   ├── sellers.php         # Seller applications
│   ├── moderation.php      # Content moderation
│   ├── reports.php         # Analytics & reports
│   └── reviews.php         # Review management
├── buyer/                   # Buyer functionality
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Order placement
│   ├── orders.php          # Order history
│   └── review_product.php  # Product reviews
├── seller/                  # Seller dashboard
│   ├── dashboard.php       # Seller overview
│   ├── products.php        # Product management
│   ├── add_product.php     # Add new products
│   ├── edit_product.php    # Edit existing products
│   ├── orders.php          # Order management
│   ├── apply.php           # Seller application
│   └── guidelines.php      # Seller guidelines
├── includes/                # Core system files
│   ├── init.php            # System initialization
│   ├── config.php          # Configuration settings
│   ├── db_connect.php      # Database connection
│   ├── auth_check.php      # Authentication functions
│   ├── admin_header.php    # Admin page header
│   ├── buyer_header.php    # Main site header
│   ├── admin_sidebar.php   # Admin navigation
│   └── seller_sidebar.php  # Seller navigation
├── assets/                  # Frontend resources
│   ├── css/style.css       # Main stylesheet
│   ├── js/main.js          # JavaScript functionality
│   └── images/             # System images
├── uploads/                 # File uploads
│   └── products/           # Product images
├── index.php               # Homepage
├── products.php            # Product catalog
├── category.php            # Category browsing
├── search.php              # Search functionality
├── product.php             # Product details
├── contact.php             # Contact form
├── about.php               # About page
├── profile.php             # User profiles
├── messages.php            # User messaging
└── setup_database.php      # Database setup
```

### 🗄️ Database Schema

#### Core Tables
- **users**: User accounts and authentication
- **sellers**: Seller-specific information and approval status
- **categories**: Product categorization system
- **products**: Product catalog with details and images
- **cart_items**: Shopping cart management
- **orders**: Order information and tracking
- **order_items**: Individual items within orders
- **contact_messages**: Contact form submissions
- **messages**: User-to-user messaging system
- **reviews**: Product reviews and ratings

---

## 👥 User Roles & Workflows

### 🛒 Buyer Workflow
1. **Registration/Login**: Create account or sign in
2. **Browse Products**: Explore categories and search items
3. **Product Details**: View detailed product information
4. **Add to Cart**: Select quantities and add items
5. **Checkout**: Provide shipping and payment information
6. **Order Tracking**: Monitor order status and history
7. **Product Reviews**: Rate and review purchased items
8. **Profile Management**: Update personal information

### 🏪 Seller Workflow
1. **Application**: Submit seller application with business details
2. **Approval**: Admin reviews and approves seller status
3. **Dashboard Access**: Access seller-specific dashboard
4. **Product Management**: Add, edit, and manage product listings
5. **Order Processing**: View and update order statuses
6. **Inventory Tracking**: Monitor stock levels and sales
7. **Guidelines Compliance**: Follow platform selling guidelines

### 👨‍💼 Admin Workflow
1. **Dashboard Overview**: Monitor platform statistics
2. **User Management**: Approve/manage buyers and sellers
3. **Content Moderation**: Review and approve products
4. **Order Oversight**: Monitor all platform transactions
5. **Category Management**: Organize product categories
6. **Contact Management**: Handle customer inquiries
7. **Analytics & Reporting**: Generate platform insights
8. **System Maintenance**: Ensure platform functionality

---

## 🛠️ Technical Specifications

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Custom CSS with responsive design
- **Icons**: Font Awesome 6.0
- **Server**: Apache/Nginx compatible

### Key Features Implementation
- **Session Management**: Secure user authentication
- **File Upload**: Product image management with validation
- **Database Transactions**: Ensure data integrity
- **Input Sanitization**: Prevent SQL injection and XSS
- **Responsive Design**: Mobile-friendly interface
- **Role-Based Access**: Conditional content display
- **Search Functionality**: Advanced product filtering
- **Cart Management**: Persistent shopping cart
- **Order Processing**: Complete e-commerce workflow

---

## 🚀 Installation & Setup

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled

### Installation Steps

1. **Clone/Download Project**
   ```bash
   git clone [repository-url] dhaqan_dhawr
   cd dhaqan_dhawr
   ```

2. **Database Configuration**
   - Create MySQL database: `dhaqan_dhowr`
   - Update `includes/config.php` with database credentials
   - Run database setup: `http://yoursite.com/setup_database.php`

3. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   ```

4. **Web Server Configuration**
   - Point document root to project directory
   - Ensure PHP modules: mysqli, pdo, gd, fileinfo
   - Enable URL rewriting if needed

5. **Admin Account Setup**
   - Register first user account
   - Manually set role to 'admin' in database
   - Access admin panel: `http://yoursite.com/admin/`

### Configuration Files
- `includes/config.php`: Database and site settings
- `includes/db_connect.php`: Database connection parameters
- `assets/css/style.css`: Styling customization

---

## 🎨 Design & User Experience

### Color Scheme
- **Primary**: #1e40af (Blue)
- **Secondary**: #39475b (Dark Blue-Gray)
- **Accent**: #f9fafb (Light Gray)
- **Success**: #10b981 (Green)
- **Warning**: #f59e0b (Orange)
- **Error**: #ef4444 (Red)

### UI Components
- **Navigation**: Role-specific navigation menus
- **Cards**: Consistent card-based layout
- **Forms**: Standardized form styling
- **Tables**: Responsive data tables
- **Modals**: Interactive popup dialogs
- **Alerts**: User feedback system

### Responsive Design
- Mobile-first approach
- Breakpoints: 768px, 1024px, 1200px
- Flexible grid system
- Touch-friendly interface

---

## 📈 Features by Category

### Cultural Heritage Focus
- **Traditional Tools**: Food preparation, clothing, cleaning tools
- **Animal Care**: Watering tools and livestock equipment
- **Cultural Foods**: Traditional Somali cuisine items
- **Documentation**: Preserve cultural knowledge and practices
- **Global Access**: Connect diaspora with heritage items

### E-Commerce Features
- **Product Catalog**: Organized by cultural categories
- **Search & Filter**: Find items by name, category, price
- **Shopping Cart**: Persistent cart across sessions
- **Checkout**: Multiple payment methods support
- **Order Management**: Track orders from placement to delivery
- **Reviews**: Community-driven product feedback

### Administrative Features
- **User Management**: Comprehensive user oversight
- **Content Moderation**: Ensure quality and appropriateness
- **Analytics**: Sales, user, and product insights
- **Communication**: Handle customer inquiries efficiently
- **Reporting**: Generate business intelligence reports

---

## 🔒 Security Features

### Authentication & Authorization
- Secure password hashing (PHP password_hash)
- Session-based authentication
- Role-based access control
- CSRF protection on forms
- Input validation and sanitization

### Data Protection
- SQL injection prevention (prepared statements)
- XSS protection (output escaping)
- File upload validation
- Secure file storage
- Database transaction integrity

### Privacy & Compliance
- User data protection
- Secure password requirements
- Session timeout management
- Audit trail for admin actions

---

## 🌐 Deployment Guide

### Production Checklist
- [ ] Update database credentials in `config.php`
- [ ] Set production base URLs
- [ ] Configure SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Enable error logging
- [ ] Configure backup procedures
- [ ] Test all functionality
- [ ] Set up monitoring

### Performance Optimization
- Enable PHP OPcache
- Configure database indexing
- Implement image optimization
- Use CDN for static assets
- Enable gzip compression
- Set up caching headers

---

## 🤝 Contributing

### Development Guidelines
- Follow PSR coding standards
- Use meaningful variable names
- Comment complex logic
- Test all functionality
- Maintain responsive design
- Ensure cross-browser compatibility

### Feature Requests
- Cultural authenticity priority
- User experience focus
- Performance considerations
- Security implications
- Scalability planning

---

## 📞 Support & Maintenance

### Regular Maintenance
- Database backup procedures
- Security update monitoring
- Performance optimization
- User feedback integration
- Feature enhancement planning

### Troubleshooting
- Check error logs in `logs/` directory
- Verify database connections
- Confirm file permissions
- Test user workflows
- Monitor system resources

---

## 📄 License & Credits

### Project Information
- **Project Name**: Dhaqan Dhowr Cultural Marketplace
- **Version**: 1.0.0
- **Development**: Custom PHP/MySQL application
- **Purpose**: Cultural heritage preservation and commerce

### Acknowledgments
- Somali cultural community for inspiration
- Traditional artisans and cultural practitioners
- Beta testers and feedback providers
- Open source community for tools and resources

---

## 📋 Version History

### v1.0.0 (Current)
- Complete multi-role authentication system
- Full e-commerce functionality
- Comprehensive admin dashboard
- Cultural heritage focus
- Responsive design implementation
- Security features integration
- Contact and messaging system
- Product review system
- Order management workflow

---

*For technical support or questions, please use the contact form within the application or refer to the documentation above.*

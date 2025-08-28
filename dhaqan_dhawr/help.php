<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'Help Center';
$base_url = '';

include 'includes/header.php';
?>

<main>
    <div class="container">
        <div class="help-container">
            <div class="help-header">
                <h1>Help Center</h1>
                <p>Find answers to common questions and get support</p>
            </div>

            <div class="help-content">
                <!-- Getting Started -->
                <div class="help-section">
                    <h2>Getting Started</h2>
                    
                    <div class="faq-item">
                        <h3>How do I create an account?</h3>
                        <p>To create an account, click the "Register" button in the top navigation. You'll need to provide your name, email address, and choose a password. You can register as either a buyer or a seller.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>What's the difference between buyer and seller accounts?</h3>
                        <p>Buyer accounts allow you to browse and purchase products. Seller accounts allow you to list and sell your own products after approval from our admin team.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I become a seller?</h3>
                        <p>After registering, you can apply to become a seller by visiting the seller application page. You'll need to provide information about your shop, location, and the types of products you plan to sell.</p>
                    </div>
                </div>

                <!-- Shopping -->
                <div class="help-section">
                    <h2>Shopping & Orders</h2>
                    
                    <div class="faq-item">
                        <h3>How do I browse products?</h3>
                        <p>You can browse products by category, use the search function, or view featured products on the homepage. Each product page shows detailed information, images, and seller details.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I add items to my cart?</h3>
                        <p>On any product page, select the quantity you want and click "Add to Cart". You must be logged in to add items to your cart.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>What payment methods do you accept?</h3>
                        <p>We accept cash on delivery, bank transfers, and mobile money services. Payment options may vary by location.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I track my order?</h3>
                        <p>You can track your order status in your account dashboard under "My Orders". The seller will update the order status as it progresses.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>What if I want to cancel my order?</h3>
                        <p>You can cancel orders that are still in "pending" status. Once an order is being processed, you'll need to contact the seller directly.</p>
                    </div>
                </div>

                <!-- Selling -->
                <div class="help-section">
                    <h2>Selling on Dhaqan Dhowr</h2>
                    
                    <div class="faq-item">
                        <h3>What types of products can I sell?</h3>
                        <p>We specialize in authentic Somali cultural items including traditional clothing, jewelry, handicrafts, ceremonial objects, and cultural artifacts. All products must be authentic and culturally significant.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I add products to my shop?</h3>
                        <p>Once your seller account is approved, you can add products through your seller dashboard. You'll need to provide product details, images, pricing, and stock information.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How long does product approval take?</h3>
                        <p>Product approval typically takes 1-3 business days. Our team reviews each product to ensure it meets our authenticity and quality standards.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I manage my orders?</h3>
                        <p>You can view and manage all orders in your seller dashboard. Update order status, communicate with buyers, and track your sales performance.</p>
                    </div>
                </div>

                <!-- Account & Security -->
                <div class="help-section">
                    <h2>Account & Security</h2>
                    
                    <div class="faq-item">
                        <h3>How do I change my password?</h3>
                        <p>You can change your password in your profile settings. Go to "My Profile" and use the "Change Password" section.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How do I update my profile information?</h3>
                        <p>Visit your profile page to update your personal information, contact details, and preferences.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Is my personal information secure?</h3>
                        <p>Yes, we take security seriously. All personal information is encrypted and stored securely. We never share your information with third parties without your consent.</p>
                    </div>
                </div>

                <!-- Shipping & Returns -->
                <div class="help-section">
                    <h2>Shipping & Returns</h2>
                    
                    <div class="faq-item">
                        <h3>How much does shipping cost?</h3>
                        <p>Shipping costs vary by location and are calculated at checkout. Local delivery is typically faster and less expensive than international shipping.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>How long does shipping take?</h3>
                        <p>Local shipping typically takes 2-5 business days. International shipping can take 1-3 weeks depending on the destination and shipping method.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>What is your return policy?</h3>
                        <p>We accept returns within 14 days of delivery for items that are damaged or not as described. Contact the seller directly to initiate a return.</p>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="help-section">
                    <h2>Still Need Help?</h2>
                    <p>If you couldn't find the answer you're looking for, our support team is here to help.</p>
                    
                    <div class="support-options">
                        <div class="support-option">
                            <div class="support-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="support-info">
                                <h3>Email Support</h3>
                                <p>Send us an email at support@dhaqandhawr.com</p>
                                <small>We typically respond within 24 hours</small>
                            </div>
                        </div>
                        
                        <div class="support-option">
                            <div class="support-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="support-info">
                                <h3>Phone Support</h3>
                                <p>Call us at +252 61 123 4567</p>
                                <small>Monday - Friday, 9:00 AM - 6:00 PM (EAT)</small>
                            </div>
                        </div>
                        
                        <div class="support-option">
                            <div class="support-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="support-info">
                                <h3>Live Chat</h3>
                                <p>Chat with our support team</p>
                                <small>Available during business hours</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-cta">
                        <a href="contact.php" class="btn btn-primary">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.help-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 0;
}

.help-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.help-header h1 {
    margin: 0 0 1rem 0;
    font-size: 2.5rem;
}

.help-header p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
}

.help-content {
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

.help-section {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
}

.help-section h2 {
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.faq-item {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #f8f9fa;
}

.faq-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.faq-item h3 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.1rem;
}

.faq-item p {
    margin: 0;
    line-height: 1.6;
    color: #666;
}

.support-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.support-option {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.support-icon {
    width: 50px;
    height: 50px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.support-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.1rem;
}

.support-info p {
    margin: 0 0 0.25rem 0;
    color: #666;
    font-weight: 500;
}

.support-info small {
    color: #999;
    font-size: 0.9rem;
}

.contact-cta {
    text-align: center;
    margin-top: 2rem;
}

.contact-cta .btn {
    min-width: 200px;
}

@media (max-width: 768px) {
    .help-header h1 {
        font-size: 2rem;
    }
    
    .support-options {
        grid-template-columns: 1fr;
    }
    
    .support-option {
        flex-direction: column;
        text-align: center;
    }
    
    .support-icon {
        align-self: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>



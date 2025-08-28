<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'Terms of Service';
$base_url = '';

include 'includes/header.php';
?>

<main>
    <div class="container">
        <div class="policy-container">
            <div class="policy-header">
                <h1>Terms of Service</h1>
                <p>Last updated: <?= date('F j, Y'); ?></p>
            </div>

            <div class="policy-content">
                <div class="policy-section">
                    <h2>Agreement to Terms</h2>
                    <p>By accessing and using Dhaqan Dhowr ("the Website"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                </div>

                <div class="policy-section">
                    <h2>Description of Service</h2>
                    <p>Dhaqan Dhowr is an online marketplace that connects buyers and sellers of authentic Somali cultural items. Our platform facilitates the sale and purchase of traditional clothing, jewelry, handicrafts, and other cultural artifacts.</p>
                </div>

                <div class="policy-section">
                    <h2>User Accounts</h2>
                    
                    <h3>Account Creation</h3>
                    <p>To use certain features of our service, you must create an account. You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate, current, and complete.</p>
                    
                    <h3>Account Security</h3>
                    <p>You are responsible for safeguarding your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account.</p>
                    
                    <h3>Account Types</h3>
                    <ul>
                        <li><strong>Buyer Accounts:</strong> Allow you to browse and purchase products</li>
                        <li><strong>Seller Accounts:</strong> Allow you to list and sell products after approval</li>
                        <li><strong>Admin Accounts:</strong> Reserved for platform administrators</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Seller Responsibilities</h2>
                    <p>As a seller on our platform, you agree to:</p>
                    <ul>
                        <li>Provide accurate and complete product information</li>
                        <li>Ensure all products are authentic and culturally significant</li>
                        <li>Maintain adequate stock levels</li>
                        <li>Process orders promptly and communicate with buyers</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Maintain high standards of customer service</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Buyer Responsibilities</h2>
                    <p>As a buyer on our platform, you agree to:</p>
                    <ul>
                        <li>Provide accurate shipping and payment information</li>
                        <li>Pay for orders in a timely manner</li>
                        <li>Communicate with sellers regarding order issues</li>
                        <li>Respect the cultural significance of products</li>
                        <li>Follow our return and refund policies</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Prohibited Activities</h2>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Use the service for any illegal or unauthorized purpose</li>
                        <li>Violate any applicable laws or regulations</li>
                        <li>Infringe on the rights of others</li>
                        <li>Upload or transmit harmful content</li>
                        <li>Attempt to gain unauthorized access to our systems</li>
                        <li>Interfere with the proper functioning of the service</li>
                        <li>Use automated systems to access the service</li>
                        <li>Impersonate another person or entity</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Product Authenticity</h2>
                    <p>We are committed to maintaining the authenticity of cultural items on our platform. All products must be:</p>
                    <ul>
                        <li>Authentic and culturally significant</li>
                        <li>Accurately described and photographed</li>
                        <li>Compliant with cultural preservation standards</li>
                        <li>Legally obtained and sold</li>
                    </ul>
                    <p>We reserve the right to remove products that do not meet these standards.</p>
                </div>

                <div class="policy-section">
                    <h2>Payment and Transactions</h2>
                    <p>All transactions are processed securely through our platform. You agree to:</p>
                    <ul>
                        <li>Provide valid payment information</li>
                        <li>Pay all applicable fees and taxes</li>
                        <li>Not engage in fraudulent transactions</li>
                        <li>Respect the payment terms agreed upon with sellers</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Intellectual Property</h2>
                    <p>The content on our website, including text, graphics, logos, and software, is protected by copyright and other intellectual property laws. You may not reproduce, distribute, or create derivative works without our permission.</p>
                </div>

                <div class="policy-section">
                    <h2>Privacy</h2>
                    <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the service, to understand our practices.</p>
                </div>

                <div class="policy-section">
                    <h2>Disclaimers</h2>
                    <p>Our service is provided "as is" without warranties of any kind. We do not guarantee:</p>
                    <ul>
                        <li>Uninterrupted or error-free service</li>
                        <li>Accuracy of product information</li>
                        <li>Availability of products</li>
                        <li>Compatibility with all devices or browsers</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Limitation of Liability</h2>
                    <p>In no event shall Dhaqan Dhowr be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or relating to your use of the service.</p>
                </div>

                <div class="policy-section">
                    <h2>Indemnification</h2>
                    <p>You agree to indemnify and hold harmless Dhaqan Dhowr from any claims, damages, or expenses arising from your use of the service or violation of these terms.</p>
                </div>

                <div class="policy-section">
                    <h2>Termination</h2>
                    <p>We may terminate or suspend your account at any time for violations of these terms or for any other reason. You may also terminate your account at any time by contacting us.</p>
                </div>

                <div class="policy-section">
                    <h2>Changes to Terms</h2>
                    <p>We reserve the right to modify these terms at any time. We will notify users of significant changes by posting the new terms on this page. Your continued use of the service constitutes acceptance of the modified terms.</p>
                </div>

                <div class="policy-section">
                    <h2>Governing Law</h2>
                    <p>These terms shall be governed by and construed in accordance with the laws of Somalia, without regard to its conflict of law provisions.</p>
                </div>

                <div class="policy-section">
                    <h2>Contact Information</h2>
                    <p>If you have any questions about these Terms of Service, please contact us:</p>
                    <div class="contact-info">
                        <p><strong>Email:</strong> legal@dhaqandhawr.com</p>
                        <p><strong>Phone:</strong> +252 61 123 4567</p>
                        <p><strong>Address:</strong> Mogadishu, Somalia</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.policy-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.policy-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.policy-header h1 {
    margin: 0 0 1rem 0;
    font-size: 2.5rem;
}

.policy-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.policy-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.policy-section {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
}

.policy-section h2 {
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.policy-section h3 {
    margin: 1.5rem 0 1rem 0;
    color: #333;
    font-size: 1.2rem;
}

.policy-section p {
    line-height: 1.6;
    color: #666;
    margin-bottom: 1rem;
}

.policy-section ul {
    margin: 1rem 0;
    padding-left: 2rem;
}

.policy-section li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
    color: #666;
}

.policy-section strong {
    color: #333;
}

.contact-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.contact-info p {
    margin: 0.5rem 0;
}

@media (max-width: 768px) {
    .policy-header h1 {
        font-size: 2rem;
    }
    
    .policy-section {
        padding: 1.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>



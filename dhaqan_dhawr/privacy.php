<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'Privacy Policy';
$base_url = '';

include 'includes/header.php';
?>

<main>
    <div class="container">
        <div class="policy-container">
            <div class="policy-header">
                <h1>Privacy Policy</h1>
                <p>Last updated: <?= date('F j, Y'); ?></p>
            </div>

            <div class="policy-content">
                <div class="policy-section">
                    <h2>Introduction</h2>
                    <p>Dhaqan Dhowr ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.</p>
                </div>

                <div class="policy-section">
                    <h2>Information We Collect</h2>
                    
                    <h3>Personal Information</h3>
                    <p>We may collect personal information that you voluntarily provide to us when you:</p>
                    <ul>
                        <li>Create an account</li>
                        <li>Make a purchase</li>
                        <li>Contact our support team</li>
                        <li>Apply to become a seller</li>
                        <li>Subscribe to our newsletter</li>
                    </ul>
                    
                    <p>This information may include:</p>
                    <ul>
                        <li>Name and contact information (email, phone, address)</li>
                        <li>Account credentials</li>
                        <li>Payment information</li>
                        <li>Communication preferences</li>
                    </ul>

                    <h3>Automatically Collected Information</h3>
                    <p>When you visit our website, we automatically collect certain information about your device, including:</p>
                    <ul>
                        <li>IP address</li>
                        <li>Browser type and version</li>
                        <li>Operating system</li>
                        <li>Pages visited and time spent</li>
                        <li>Referring website</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide and maintain our services</li>
                        <li>Process transactions and send related information</li>
                        <li>Send technical notices and support messages</li>
                        <li>Respond to your comments and questions</li>
                        <li>Improve our website and services</li>
                        <li>Protect against fraud and abuse</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Information Sharing</h2>
                    <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except in the following circumstances:</p>
                    <ul>
                        <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our website and providing services.</li>
                        <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights and safety.</li>
                        <li><strong>Business Transfers:</strong> In the event of a merger or acquisition, your information may be transferred to the new entity.</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Data Security</h2>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:</p>
                    <ul>
                        <li>Encryption of sensitive data</li>
                        <li>Regular security assessments</li>
                        <li>Access controls and authentication</li>
                        <li>Secure data storage practices</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate information</li>
                        <li>Request deletion of your information</li>
                        <li>Opt-out of marketing communications</li>
                        <li>Withdraw consent for data processing</li>
                    </ul>
                    <p>To exercise these rights, please contact us using the information provided below.</p>
                </div>

                <div class="policy-section">
                    <h2>Cookies and Tracking</h2>
                    <p>We use cookies and similar tracking technologies to enhance your experience on our website. Cookies are small files stored on your device that help us:</p>
                    <ul>
                        <li>Remember your preferences</li>
                        <li>Analyze website traffic</li>
                        <li>Improve our services</li>
                        <li>Provide personalized content</li>
                    </ul>
                    <p>You can control cookie settings through your browser preferences.</p>
                </div>

                <div class="policy-section">
                    <h2>Children's Privacy</h2>
                    <p>Our services are not intended for children under the age of 13. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.</p>
                </div>

                <div class="policy-section">
                    <h2>Changes to This Policy</h2>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date. We encourage you to review this policy periodically.</p>
                </div>

                <div class="policy-section">
                    <h2>Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
                    <div class="contact-info">
                        <p><strong>Email:</strong> privacy@dhaqandhawr.com</p>
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



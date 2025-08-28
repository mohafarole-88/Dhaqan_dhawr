<?php
require_once __DIR__ . '/../includes/init.php';

$page_title = "Seller Guidelines";
include '../includes/header.php';
?>

<main class="guidelines-main">
    <div class="container">
        <div class="guidelines-header">
            <h1>Seller Guidelines</h1>
            <p>Essential information and rules for selling on Dhaqan Dhowr marketplace.</p>
        </div>

        <div class="guidelines-content">
            <section class="guideline-section">
                <h2>Getting Started</h2>
                <div class="guideline-item">
                    <h3>1. Apply to Become a Seller</h3>
                    <p>To start selling on Dhaqan Dhowr, you need to apply for seller status. This helps us maintain quality and authenticity of cultural items.</p>
                    <ul>
                        <li>Fill out the seller application form</li>
                        <li>Provide your shop name and location</li>
                        <li>Write a brief bio about your cultural expertise</li>
                        <li>Wait for admin approval (usually within 24-48 hours)</li>
                    </ul>
                    <?php if (!isLoggedIn()): ?>
                        <a href="../Auth/register.php" class="btn btn-primary">Register to Apply</a>
                    <?php elseif (!isApprovedSeller()): ?>
                        <a href="apply.php" class="btn btn-primary">Apply Now</a>
                    <?php endif; ?>
                </div>

                <div class="guideline-item">
                    <h3>2. Set Up Your Shop</h3>
                    <p>Once approved, you can start building your shop presence:</p>
                    <ul>
                        <li>Complete your seller profile</li>
                        <li>Add your shop logo and banner</li>
                        <li>Write a detailed shop description</li>
                        <li>Set your shipping policies</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Product Guidelines</h2>
                <div class="guideline-item">
                    <h3>What You Can Sell</h3>
                    <p>Dhaqan Dhowr specializes in authentic Somali cultural items:</p>
                    <ul>
                        <li><strong>Traditional Clothing:</strong> Macawiis, Dirac, Guntiino, traditional jewelry</li>
                        <li><strong>Cultural Artifacts:</strong> Handcrafted items, traditional tools, decorative pieces</li>
                        <li><strong>Musical Instruments:</strong> Traditional Somali instruments</li>
                        <li><strong>Textiles:</strong> Handwoven fabrics, traditional patterns</li>
                        <li><strong>Cultural Books:</strong> Literature, history, language learning materials</li>
                        <li><strong>Traditional Foods:</strong> Spices, ingredients, traditional recipes</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Product Requirements</h3>
                    <ul>
                        <li><strong>Authenticity:</strong> All items must be authentic Somali cultural products</li>
                        <li><strong>Quality Images:</strong> Clear, high-quality photos from multiple angles</li>
                        <li><strong>Detailed Descriptions:</strong> Include cultural significance, materials, and usage</li>
                        <li><strong>Accurate Pricing:</strong> Fair and competitive pricing</li>
                        <li><strong>Stock Management:</strong> Keep inventory levels updated</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Prohibited Items</h3>
                    <p>The following items are not allowed:</p>
                    <ul>
                        <li>Counterfeit or replica items</li>
                        <li>Items not related to Somali culture</li>
                        <li>Dangerous or illegal items</li>
                        <li>Items that violate intellectual property rights</li>
                        <li>Mass-produced items without cultural significance</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Listing Guidelines</h2>
                <div class="guideline-item">
                    <h3>Product Information</h3>
                    <ul>
                        <li><strong>Title:</strong> Clear, descriptive titles that include key cultural terms</li>
                        <li><strong>Description:</strong> Detailed explanation of cultural significance and usage</li>
                        <li><strong>Category:</strong> Choose the most appropriate category</li>
                        <li><strong>Images:</strong> Multiple high-quality images showing all details</li>
                        <li><strong>Cultural Notes:</strong> Explain the cultural context and significance</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Pricing Strategy</h3>
                    <ul>
                        <li>Research similar items on the platform</li>
                        <li>Consider the cultural value and craftsmanship</li>
                        <li>Factor in shipping costs</li>
                        <li>Be transparent about pricing</li>
                        <li>Offer competitive but fair prices</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Customer Service</h2>
                <div class="guideline-item">
                    <h3>Communication Standards</h3>
                    <ul>
                        <li>Respond to customer inquiries within 24 hours</li>
                        <li>Be helpful and knowledgeable about your products</li>
                        <li>Provide cultural context when asked</li>
                        <li>Use respectful and professional language</li>
                        <li>Address concerns promptly and professionally</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Shipping & Handling</h3>
                    <ul>
                        <li>Ship items within 2-3 business days</li>
                        <li>Use appropriate packaging for cultural items</li>
                        <li>Provide tracking information</li>
                        <li>Handle fragile items with care</li>
                        <li>Include care instructions for traditional items</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Quality Standards</h2>
                <div class="guideline-item">
                    <h3>Maintaining Quality</h3>
                    <ul>
                        <li>Inspect all items before shipping</li>
                        <li>Ensure items are clean and well-maintained</li>
                        <li>Provide accurate condition descriptions</li>
                        <li>Stand behind the authenticity of your items</li>
                        <li>Address any quality issues promptly</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Cultural Accuracy</h3>
                    <ul>
                        <li>Provide accurate cultural information</li>
                        <li>Respect cultural traditions and practices</li>
                        <li>Be honest about item origins and history</li>
                        <li>Educate customers about cultural significance</li>
                        <li>Preserve the integrity of cultural items</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Platform Policies</h2>
                <div class="guideline-item">
                    <h3>Community Standards</h3>
                    <ul>
                        <li>Respect all community members</li>
                        <li>Maintain professional conduct</li>
                        <li>Follow platform rules and guidelines</li>
                        <li>Report any violations or concerns</li>
                        <li>Contribute positively to the community</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Fees & Payments</h3>
                    <ul>
                        <li>Platform fees are clearly communicated</li>
                        <li>Payments are processed securely</li>
                        <li>Payouts are made according to schedule</li>
                        <li>Keep accurate financial records</li>
                        <li>Report any payment issues promptly</li>
                    </ul>
                </div>
            </section>

            <section class="guideline-section">
                <h2>Support & Resources</h2>
                <div class="guideline-item">
                    <h3>Getting Help</h3>
                    <ul>
                        <li>Contact support for technical issues</li>
                        <li>Use the help center for common questions</li>
                        <li>Join seller community discussions</li>
                        <li>Attend seller training sessions</li>
                        <li>Stay updated with platform announcements</li>
                    </ul>
                </div>

                <div class="guideline-item">
                    <h3>Additional Resources</h3>
                    <ul>
                        <li><a href="../help.php">Help Center</a> - Comprehensive guides and FAQs</li>
                        <li><a href="../contact.php">Contact Support</a> - Get help with specific issues</li>
                        <li><a href="../shipping.php">Shipping Information</a> - Shipping guidelines and costs</li>
                        <li><a href="../returns.php">Returns Policy</a> - Understanding return procedures</li>
                    </ul>
                </div>
            </section>
        </div>

        <div class="guidelines-actions">
            <?php if (isLoggedIn() && isApprovedSeller()): ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Seller Dashboard</a>
            <?php elseif (isLoggedIn()): ?>
                <a href="apply.php" class="btn btn-primary">Apply to Become a Seller</a>
            <?php else: ?>
                <a href="../Auth/register.php" class="btn btn-primary">Register to Start Selling</a>
            <?php endif; ?>
            <a href="../contact.php" class="btn btn-outline">Contact Support</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

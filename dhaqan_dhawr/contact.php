<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'Contact Us';
$base_url = '';

$errors = [];
$success_message = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    if (empty($errors)) {
        try {
            // Save message to database
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            $success_message = 'Thank you for your message! We will get back to you soon.';
            
            // Clear form data
            $name = $email = $subject = $message = '';
        } catch (PDOException $e) {
            $errors[] = 'There was an error sending your message. Please try again.';
        }
    }
}

include 'includes/buyer_header.php';
?>

<main class="contact-main">
    <div class="container">
        <div class="contact-container">
            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>Have questions or need assistance? We're here to help!</p>
            </div>

            <div class="contact-content">
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <p>We'd love to hear from you. Whether you have a question about our products, need help with your account, or want to learn more about Somali culture, our team is ready to assist you.</p>
                    
                    <div class="categories-grid">
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h3>Email Us</h3>
                                <p>axmedawmaxamed33@gmail.com</p>
                                <small>We typically respond within 24 hours</small>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h3>Call Us</h3>
                                <p>+252907896155</p>
                                <small>Saturday - Thursday, 9:00 AM - 6:00 PM (EAT)</small>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h3>Visit Us</h3>
                                <p>Garoowe, Somalia</p>
                                <small>By appointment only</small>
                            </div>
                        </div>
                    </div>

                    <div class="about-section">
                        <h3>Frequently Asked Questions</h3>
                        <div class="categories-grid">
                            <div class="category-card">
                                <div class="category-content">
                                    <div class="category-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <h3>How do I become a seller?</h3>
                                    <p>To become a seller, you need to register for an account and then apply through our seller application process. We review all applications to ensure quality and authenticity.</p>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-content">
                                    <div class="category-icon">
                                        <i class="fas fa-certificate"></i>
                                    </div>
                                    <h3>How do I know products are authentic?</h3>
                                    <p>All products on our platform are carefully vetted by our team. We work directly with verified artisans and cultural experts to ensure authenticity.</p>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-content">
                                    <div class="category-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <h3>What payment methods do you accept?</h3>
                                    <p>We accept various payment methods including cash on delivery, bank transfers, and mobile money services popular in the region.</p>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-content">
                                    <div class="category-icon">
                                        <i class="fas fa-shipping-fast"></i>
                                    </div>
                                    <h3>Do you ship internationally?</h3>
                                    <p>Yes, we offer international shipping to most countries. Shipping costs and delivery times vary by location.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form-container">
                    <h2>Send us a Message</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="contact-form">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($subject ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required><?= htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary w-100">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>


<?php include 'includes/footer.php'; ?>



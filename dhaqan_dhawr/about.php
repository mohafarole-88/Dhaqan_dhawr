<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'About Us';
$base_url = '';

include 'includes/buyer_header.php';
?>

<main class="about-main">
    <div class="container">
        <div class="about-container">
            <div class="about-header">
                <h1>About Dhaqan Dhowr</h1>
                <p class="subtitle">Preserving Somali Cultural Heritage Through Digital Commerce</p>
            </div>

            <div class="about-content">
                <div class="about-section">
                    <h2>Our Mission</h2>
                    <p>Dhaqan Dhowr is dedicated to preserving and promoting authentic Somali cultural treasures through a modern, accessible online marketplace. We connect local artisans and cultural experts with buyers worldwide, ensuring that traditional Somali craftsmanship and cultural heritage continue to thrive in the digital age.</p>
                </div>

                <div class="about-section">
                    <h2>What We Do</h2>
                    <div class="categories-grid">
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <h3>Cultural Marketplace</h3>
                                <p>We provide a platform for authentic Somali cultural items, from traditional clothing and jewelry to ceremonial objects and handicrafts.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3>Artisan Support</h3>
                                <p>We empower local artisans and cultural experts by providing them with tools and resources to reach global markets.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h3>Authenticity Guarantee</h3>
                                <p>Every product on our platform is carefully vetted to ensure authenticity and cultural significance.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <h3>Global Reach</h3>
                                <p>We connect Somali communities worldwide with their cultural heritage and provide access to authentic items.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="about-section">
                    <h2>Our Values</h2>
                    <div class="categories-grid">
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h3>Cultural Preservation</h3>
                                <p>We are committed to preserving Somali cultural heritage for future generations.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <h3>Community Support</h3>
                                <p>We support local communities and artisans by providing fair opportunities and compensation.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h3>Quality Assurance</h3>
                                <p>We maintain high standards for all products and services on our platform.</p>
                            </div>
                        </div>
                        
                        <div class="category-card">
                            <div class="category-content">
                                <div class="category-icon">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <h3>Ethical Commerce</h3>
                                <p>We promote fair trade practices and respect for cultural traditions.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="about-section">
                    <h2>Our Story</h2>
                    <p>Dhaqan Dhowr was founded with a simple yet powerful vision: to create a bridge between traditional Somali craftsmanship and the modern digital world. Our founders recognized the need to preserve and promote Somali cultural heritage while providing economic opportunities for local artisans.</p>
                    
                    <p>Today, we are proud to serve as a trusted platform where cultural authenticity meets modern convenience. Our community of sellers includes master craftsmen, cultural experts, and heritage preservationists who share our passion for Somali culture.</p>
                </div>

                <div class="about-section">
                    <h2>Join Our Community</h2>
                    <p>Whether you're a buyer looking for authentic Somali cultural items, a seller wanting to share your craftsmanship, or simply someone interested in Somali culture, we welcome you to join our community.</p>
                </div>
            </div>
        </div>
    </div>
</main>


<?php include 'includes/footer.php'; ?>



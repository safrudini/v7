<?php
// Get current year for copyright
$currentYear = date('Y');
?>
    </div><!-- End of .main-wrapper -->
    
    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="footer-title">Rud's Store</h5>
                    <p class="footer-text">
                        Penyedia layanan isi ulang kuota XL/Axis, akun premium, dan berbagai layanan digital lainnya dengan harga terbaik dan pelayanan tercepat.
                    </p>
                    <div class="social-links">
                        <a href="https://wa.me/6287847526737" class="social-link" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://facebook.com/rudsstore" class="social-link" target="_blank">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://instagram.com/rudsstore" class="social-link" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://t.me/rudsstore" class="social-link" target="_blank">
                            <i class="fab fa-telegram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-subtitle">Layanan</h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/services.php#kuota">Kuota XL/Axis</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php#premium">Akun Premium</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php#pulsa">Pulsa All Operator</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php#voucher">Voucher Game</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-subtitle">Bantuan</h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/tutorial.php">Tutorial</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/unreg.html">Cara Unreg</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/area.html">Cek Area</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6 class="footer-subtitle">Kontak Kami</h6>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>+62 878-4752-6737</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@rudsstore.my.id</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Customer Support</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="footer-divider">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright-text">
                        &copy; <?php echo $currentYear; ?> Rud's Store. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links">
                        <a href="<?php echo SITE_URL; ?>/privacy.php">Privacy Policy</a>
                        <a href="<?php echo SITE_URL; ?>/terms.php">Terms of Service</a>
                        <a href="<?php echo SITE_URL; ?>/sitemap.php">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- WhatsApp Float Button -->
        <div id="whatsapp-float">
            <a href="https://wa.me/6287847526737?text=Halo%20Rud's%20Store,%20saya%20mau%20tanya%20tentang%20layanan..." 
               target="_blank" class="whatsapp-float-btn">
                <i class="fab fa-whatsapp"></i>
            </a>
        </div>
        
        <!-- Scroll to Top Button -->
        <button id="scrollToTop" class="scroll-to-top">
            <i class="fas fa-arrow-up"></i>
        </button>
    </footer>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/scripts.js"></script>
    
    <?php if ($isAdmin): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    <?php elseif ($isReseller): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/reseller.js"></script>
    <?php endif; ?>
    
    <!-- Initialize AOS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true
                });
            }
            
            // Hide loading spinner
            setTimeout(() => {
                const loadingSpinner = document.getElementById('loading-spinner');
                if (loadingSpinner) {
                    loadingSpinner.style.display = 'none';
                }
            }, 500);
            
            // Scroll to top button
            const scrollToTopBtn = document.getElementById('scrollToTop');
            if (scrollToTopBtn) {
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.style.display = 'block';
                    } else {
                        scrollToTopBtn.style.display = 'none';
                    }
                });
                
                scrollToTopBtn.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
            
            // WhatsApp float button
            const whatsappFloat = document.getElementById('whatsapp-float');
            if (whatsappFloat) {
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        whatsappFloat.style.display = 'block';
                    } else {
                        whatsappFloat.style.display = 'none';
                    }
                });
            }
        });
    </script>
    
    <!-- Additional Scripts for Specific Pages -->
    <?php if (isset($additionalScripts)): ?>
    <?php foreach ($additionalScripts as $script): ?>
    <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Scripts for Specific Pages -->
    <?php if (isset($inlineScripts)): ?>
    <script>
        <?php echo $inlineScripts; ?>
    </script>
    <?php endif; ?>
</body>
</html>
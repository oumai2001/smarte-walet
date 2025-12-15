</div> <!-- Fin content-wrapper -->
    </main>
    
    <!-- Footer Principal -->
    <footer class="main-footer">
        <div class="footer-container">
            <!-- Section À propos -->
            <div class="footer-section">
                <h3>
                    <i class="fas fa-chart-line"></i> Smarte Walet
                </h3>
                <p class="footer-description">
                    Solution professionnelle de gestion financière pour suivre, analyser et optimiser vos revenus et dépenses en temps réel.
                </p>
            </div>
            
            <!-- Liens Rapides -->
            <div class="footer-section">
                <h4>Liens Rapides</h4>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-angle-right"></i> Dashboard</a></li>
                    <li><a href="incomes.php"><i class="fas fa-angle-right"></i> Mes Revenus</a></li>
                    <li><a href="expenses.php"><i class="fas fa-angle-right"></i> Mes Dépenses</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Barre de copyright -->
        <div class="footer-bottom">
            <div class="footer-container">
                <p class="copyright">
                    &copy; <?= date('Y') ?> Smarte Walet. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Bouton retour en haut -->
    <button id="scrollToTop" class="scroll-to-top" title="Retour en haut">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Menu Mobile
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMobileNav = document.getElementById('closeMobileNav');
        const mobileNav = document.getElementById('mobileNav');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        function toggleMobileMenu() {
            mobileNav.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
        }
        
        mobileMenuBtn?.addEventListener('click', toggleMobileMenu);
        closeMobileNav?.addEventListener('click', toggleMobileMenu);
        mobileOverlay?.addEventListener('click', toggleMobileMenu);
        
        // Toggle Theme
        const themeToggle = document.getElementById('themeToggle');
        themeToggle?.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            const icon = themeToggle.querySelector('i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });
        
        // Charger le thème sauvegardé
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            const icon = themeToggle?.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
        
        // Scroll to Top
        const scrollBtn = document.getElementById('scrollToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollBtn?.classList.add('visible');
            } else {
                scrollBtn?.classList.remove('visible');
            }
        });
        
        scrollBtn?.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Animation des montants au chargement (Dashboard)
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.querySelectorAll('.amount').forEach(element => {
                    const finalValue = element.textContent;
                    const numericValue = parseFloat(finalValue.replace(/[^0-9.-]+/g, ''));
                    
                    if (!isNaN(numericValue)) {
                        element.textContent = '0.00 DH';
                        animateValue(element, 0, numericValue, 1000, finalValue.includes('DH'));
                    }
                });
            }, 300);
        });
        
        function animateValue(element, start, end, duration, hasCurrency = true) {
            const startTime = performance.now();
            const difference = end - start;
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeProgress = progress * (2 - progress); // easeOutQuad
                const current = start + (difference * easeProgress);
                
                const formatted = new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(current);
                
                element.textContent = hasCurrency ? formatted + ' DH' : formatted;
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }
    </script>
</body>
</html>
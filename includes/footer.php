    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-camera-reels me-2"></i>Rental Inventory System</h5>
                    <p class="text-white-50 mb-0">Communication & Design Department</p>
                    <p class="text-white-50">Equipment rental management for students and faculty.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo $basePath; ?>guest/browse.php" class="text-white-50">Browse Equipment</a></li>
                        <li><a href="<?php echo $basePath; ?>index.php" class="text-white-50">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contact</h6>
                    <ul class="list-unstyled text-white-50">
                        <li><i class="bi bi-geo-alt me-2"></i>Communication Building</li>
                        <li><i class="bi bi-envelope me-2"></i>equipment@university.edu</li>
                        <li><i class="bi bi-telephone me-2"></i>(555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center text-white-50">
                <small>&copy; <?php echo date('Y'); ?> Rental Inventory System. All rights reserved.</small>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $basePath; ?>assets/js/main.js"></script>
</body>
</html>

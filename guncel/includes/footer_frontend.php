    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo getSetting('site_name'); ?></h5>
                    <p><?php echo getSetting('site_description'); ?></p>
                    <p>
                        <i class="fas fa-map-marker-alt"></i> <?php echo getSetting('site_address'); ?><br>
                        <i class="fas fa-phone"></i> <?php echo getSetting('site_phone'); ?><br>
                        <i class="fas fa-envelope"></i> <?php echo getSetting('site_email'); ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Hızlı Linkler</h5>
                    <ul class="list-unstyled">
                        <?php
                        $pages = dbQuery("SELECT * FROM pages WHERE status = 1 LIMIT 5");
                        foreach ($pages as $page):
                        ?>
                            <li><a href="<?php echo siteUrl('page.php?slug=' . $page['slug']); ?>" class="text-white text-decoration-none"><?php echo htmlspecialchars($page['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Sosyal Medya</h5>
                    <div class="social-links">
                        <?php if (getSetting('facebook_url')): ?>
                            <a href="<?php echo getSetting('facebook_url'); ?>" class="btn btn-outline-light btn-sm me-2" target="_blank"><i class="fab fa-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('twitter_url')): ?>
                            <a href="<?php echo getSetting('twitter_url'); ?>" class="btn btn-outline-light btn-sm me-2" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('instagram_url')): ?>
                            <a href="<?php echo getSetting('instagram_url'); ?>" class="btn btn-outline-light btn-sm me-2" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('youtube_url')): ?>
                            <a href="<?php echo getSetting('youtube_url'); ?>" class="btn btn-outline-light btn-sm me-2" target="_blank"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                    <h5 class="mt-4">Bültene Kaydol</h5>
                    <form action="<?php echo siteUrl('newsletter.php'); ?>" method="POST">
                        <?php echo csrfField(); ?>
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                            <button type="submit" class="btn btn-primary">Kayıt</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="bg-black py-3">
            <div class="container text-center">
                <small><?php echo getSetting('footer_text'); ?></small>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>
</html>

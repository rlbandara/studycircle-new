<?php
// includes/footer.php: bottom of every page (uses the same $layout as header.php)
$layout = $layout ?? 'student';
?>
  </main>
<?php if ($layout === 'admin'): ?>
</div>
<?php elseif ($layout === 'student'): ?>
<footer class="sc-footer">
  <div class="container-xl">&copy; <?= date('Y') ?> StudyCircle, NSBM Green University</div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>

<?php /** @var array $page */ ?>
<section class="section container narrow">
  <h1><?= e($page['title']) ?></h1>
  <time class="muted">آخرین به‌روزرسانی: <?= e(shamsi_date($page['updated_at'])) ?></time>
  <div class="prose"><?= sanitize_html((string)$page['body']) ?></div>
</section>

<?php $a = $article; ?>
<article class="section container article-page">
  <header class="art-head">
    <h1><?= e($a['title']) ?></h1>
    <p class="art-meta">🗓 <?= e(shamsi_date($a['publish_at'] ?: $a['created_at'])) ?> · 👁 <?= fa_digits((string)$a['views_count']) ?> بازدید
      <?php if (!empty($a['author_name'])): ?> · ✍ <?= e((string)$a['author_name']) ?><?php endif; ?></p>
  </header>
  <?php if (!empty($a['image_path'])): ?><figure class="art-img"><img src="<?= e($a['image_path']) ?>" alt="<?= e($a['alt_text'] ?: $a['title']) ?>" width="860" height="430"></figure>
  <?php else: ?><div class="art-img"><?= \TT\Placeholder::svg((string)$a['title'], 860, 430) ?></div><?php endif; ?>
  <div class="prose"><?= sanitize_html((string)$a['body']) ?></div>
  <?php if ($tags): ?><p class="tag-row"><?php foreach ($tags as $t): ?><span class="badge">#<?= e($t['name']) ?></span><?php endforeach; ?></p><?php endif; ?>

  <?php if ($faqs): ?><section class="faq-block"><h2>سؤالات متداول مرتبط</h2>
    <?php foreach ($faqs as $f): ?><details class="card faq-item"><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details><?php endforeach; ?></section><?php endif; ?>

  <section class="card comment-box" style="padding:20px">
    <h2>دیدگاه شما</h2>
    <?php foreach ($comments as $c): ?><div class="cmnt"><strong><?= e($c['name']) ?></strong><p><?= e($c['body']) ?></p><time><?= e(shamsi_date($c['created_at'])) ?></time></div><?php endforeach; ?>
    <form class="form-grid" data-comment-form data-target-type="article" data-target-id="<?= (int)$a['id'] ?>">
      <?= csrf_field() ?>
      <p class="field"><label>نام *</label><input required name="name" maxlength="80"></p>
      <p class="field"><label>متن دیدگاه *</label><textarea required name="body" rows="3" maxlength="1500"></textarea></p>
      <button class="btn btn-primary btn-sm" type="submit">ارسال دیدگاه</button>
      <span class="form-note">دیدگاه پس از تأیید نمایش داده می‌شود.</span>
    </form>
  </section>

  <?php if ($related): ?><section><h2>مقالات مرتبط</h2><div class="grid g-3">
    <?php foreach ($related as $r): ?>
    <a class="card art-card" href="/maghaleh/<?= e($r['slug']) ?>"><h3><?= e($r['title']) ?></h3><p><?= e(mb_substr(strip_tags((string)$r['excerpt']),0,90)) ?></p></a>
    <?php endforeach; ?></div></section><?php endif; ?>
</article>

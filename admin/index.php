<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/layout.php';
require_admin();

$stats = [
    'Posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'Published' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'Drafts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'Categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
admin_header('Dashboard');
?>
<div class="stats">
    <?php foreach ($stats as $label => $value): ?><div class="stat"><strong><?= $value ?></strong><?= e($label) ?></div><?php endforeach; ?>
</div>
<p style="margin-top:1.5rem"><a class="button" href="/swd/07-php-blog/admin/posts/create.php">Write a post</a> <a class="button" href="/swd/07-php-blog/admin/categories/create.php">Add a category</a></p>
<?php admin_footer(); ?>

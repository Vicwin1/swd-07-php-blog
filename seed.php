<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';

$seedPosts = [
    [
        'category' => 'tutorials',
        'title' => 'Build a Better Weekly Planning Habit',
        'excerpt' => 'A practical weekly review can turn a long task list into a calmer, more focused plan.',
        'body' => "Start by collecting every loose task, reminder, and idea in one place. Then review your calendar and choose a small number of outcomes that would make the week feel successful.\n\nLeave open space for unexpected work. A realistic plan is easier to trust, easier to adjust, and much more useful than a packed schedule.",
    ],
    [
        'category' => 'tutorials',
        'title' => 'A Beginner-Friendly Guide to Writing Clearer Notes',
        'excerpt' => 'Simple structure and a little context make notes much easier to understand later.',
        'body' => "Good notes capture decisions and next steps, not just a transcript of everything that was said. Start with a descriptive heading, add the key context, and use short lists for actions.\n\nBefore you finish, write down who owns each action and when it is due. Future you should be able to understand the note without remembering the meeting.",
    ],
    [
        'category' => 'tutorials',
        'title' => 'Make Your First Project Checklist',
        'excerpt' => 'Turn a vague project into manageable steps with a lightweight checklist.',
        'body' => "Write down the result you want before listing tasks. Break that result into steps small enough to finish in one focused work session, and put them in the order they depend on each other.\n\nKeep the checklist visible and update it as you learn. It is a working guide, not a promise that the original plan can never change.",
    ],
    [
        'category' => 'tutorials',
        'title' => 'A Simple Method for Organizing Digital Files',
        'excerpt' => 'A consistent folder structure and naming convention save time every time you search.',
        'body' => "Create a small number of top-level folders that reflect how you work, then add project folders only when they are needed. Choose names that are specific and predictable rather than clever.\n\nFor files that change over time, include a date or clear version label. Spend a few minutes each month archiving finished work and removing duplicate downloads.",
    ],
    [
        'category' => 'news',
        'title' => 'What We Learned from This Month’s Community Meetup',
        'excerpt' => 'A few useful ideas and shared lessons from a lively month of community conversations.',
        'body' => "This month brought together people with different backgrounds and a shared interest in learning by doing. The most useful conversations started with specific examples rather than abstract advice.\n\nWe left with new connections, practical suggestions to try, and several ideas for future sessions. Thank you to everyone who brought a question, shared a lesson, or helped welcome someone new.",
    ],
    [
        'category' => 'news',
        'title' => 'A Fresh Look for the Blog',
        'excerpt' => 'The site has a cleaner reading experience and a simpler way to explore recent stories.',
        'body' => "We have refreshed the blog to make articles easier to browse on both large screens and phones. The updated layout gives each story more room and puts useful details close to the headline.\n\nYou can now explore posts by category or search the archive. We will keep improving the experience as the collection grows.",
    ],
    [
        'category' => 'news',
        'title' => 'New Learning Sessions Coming This Season',
        'excerpt' => 'A preview of the hands-on topics planned for upcoming community sessions.',
        'body' => "The next season of sessions will focus on practical skills that people can use immediately. Planned topics include planning a small project, sharing work for feedback, and building a sustainable learning routine.\n\nDates and registration information will be shared here as each session is confirmed. Suggestions from readers are always welcome.",
    ],
    [
        'category' => 'news',
        'title' => 'Reader Questions: Choosing What to Learn Next',
        'excerpt' => 'A few prompts to help choose a learning goal that fits your time and interests.',
        'body' => "Start with a problem you genuinely want to solve or a topic you keep returning to. A clear reason makes it easier to choose resources and keep going when the first attempts feel unfamiliar.\n\nSet a small experiment for the next two weeks. At the end, reflect on what you enjoyed, what was difficult, and whether the goal still feels worthwhile.",
    ],
    [
        'category' => 'career',
        'title' => 'How to Tell a Clearer Story About Your Work',
        'excerpt' => 'Describe the challenge, your contribution, and the result to make your experience memorable.',
        'body' => "When describing a piece of work, begin with the situation and the problem that needed attention. Explain the part you owned, the choices you made, and how you worked with other people.\n\nFinish with the outcome and what you learned. Specific details help someone understand your contribution more clearly than a list of tools or responsibilities alone.",
    ],
    [
        'category' => 'career',
        'title' => 'Prepare for a More Useful One-to-One Meeting',
        'excerpt' => 'A short agenda can make a regular check-in more focused and productive.',
        'body' => "Before the meeting, note one recent win, one current challenge, and one question where a second perspective would help. Share the topics early if there is something that needs preparation.\n\nEnd by confirming any decisions and next steps. A useful one-to-one is a two-way conversation, so leave space to listen as well as report.",
    ],
    [
        'category' => 'career',
        'title' => 'Give Feedback People Can Act On',
        'excerpt' => 'Ground feedback in an example, explain its effect, and agree on a useful next step.',
        'body' => "Choose a specific moment and describe what you observed without guessing at someone's intentions. Explain the effect it had on the work, the team, or the person receiving it.\n\nAsk for their perspective and discuss a next step together. Timely, respectful feedback is easier to understand and much easier to put into practice.",
    ],
    [
        'category' => 'career',
        'title' => 'Create a Small Portfolio of Your Progress',
        'excerpt' => 'Keep examples of your work and learning so you can reflect and communicate growth.',
        'body' => "Save a few examples that show how you approach problems, not only polished final results. Add a short note about the goal, the choices you made, and what changed after feedback.\n\nReview the collection every few months. It can reveal patterns in your interests, help you prepare for career conversations, and remind you how much you have learned.",
    ],
];

$pdo = db();
$pdo->beginTransaction();

try {
    $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($adminId === false) {
        throw new RuntimeException('Create an admin first by running create-admin.php.');
    }

    $categoryDefinitions = [
        'tutorials' => 'Tutorials',
        'news' => 'News',
        'career' => 'Career',
    ];
    $findCategory = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
    $categoryIds = [];
    foreach ($categoryDefinitions as $slug => $name) {
        $findCategory->execute(['slug' => $slug]);
        $categoryId = $findCategory->fetchColumn();
        if ($categoryId === false) {
            $insertCategory->execute(['name' => $name, 'slug' => $slug]);
            $categoryId = $pdo->lastInsertId();
        }
        $categoryIds[$slug] = (int) $categoryId;
    }

    $findPost = $pdo->prepare('SELECT id FROM posts WHERE slug = :slug LIMIT 1');
    $insertPost = $pdo->prepare(
        'INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, published_at)
         VALUES (:category_id, :author_id, :title, :slug, :excerpt, :body, \'published\', :published_at)'
    );

    $created = 0;
    $skipped = 0;
    foreach ($seedPosts as $index => $post) {
        $slug = slugify($post['title']);
        $findPost->execute(['slug' => $slug]);
        if ($findPost->fetchColumn() !== false) {
            $skipped++;
            continue;
        }

        $insertPost->execute([
            'category_id' => $categoryIds[$post['category']],
            'author_id' => (int) $adminId,
            'title' => $post['title'],
            'slug' => $slug,
            'excerpt' => $post['excerpt'],
            'body' => $post['body'],
            'published_at' => date('Y-m-d H:i:s', time() - ($index * 86400)),
        ]);
        $created++;
    }

    $pdo->commit();
    fwrite(STDOUT, "Seed complete: {$created} post(s) created, {$skipped} already existed.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

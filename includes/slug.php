<?php
/**
 * Slug generation and uniqueness helpers
 */
function generate_slug(string $title): string {
    // Normalize
    $s = mb_strtolower($title, 'UTF-8');
    // Replace non letters/digits with hyphen
    $s = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $s);
    // Trim hyphens
    $s = trim($s, '-');
    // Collapse multiple hyphens
    $s = preg_replace('/-+/', '-', $s);
    if ($s === '') {
        $s = 'project';
    }
    return $s;
}

function make_unique_project_slug(PDO $pdo, string $baseSlug, int $projectId = 0): string {
    $slug = $baseSlug;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(1) FROM projects WHERE slug = ?' . ($projectId > 0 ? ' AND id != ?' : '');
        $stmt = $pdo->prepare($sql);
        $params = [$slug];
        if ($projectId > 0) { $params[] = $projectId; }
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();
        if ($count === 0) {
            return $slug;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
}

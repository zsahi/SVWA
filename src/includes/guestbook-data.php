<?php
function guestbook_comment(int $id): ?array {
    $query = db()->prepare('SELECT id, author, message, created_at FROM messages WHERE id = ?');
    $query->execute([$id]);
    $comment = $query->fetch(PDO::FETCH_ASSOC);
    return $comment ?: null;
}
function guestbook_page(int $page, string $keyword = ''): array {
    $keyword = trim($keyword);
    $where = $keyword === '' ? '' : " WHERE author LIKE ? ESCAPE '!' OR message LIKE ? ESCAPE '!'";
    $pattern = '%'.strtr($keyword, ['!'=>'!!', '%'=>'!%', '_'=>'!_']).'%';
    $params = $keyword === '' ? [] : [$pattern, $pattern];
    $count = db()->prepare('SELECT COUNT(*) FROM messages'.$where);
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / 10));
    $page = min(max(1, $page), $pages);
    $query = db()->prepare('SELECT id, author, message, created_at FROM messages'.$where.' ORDER BY id DESC LIMIT 10 OFFSET ?');
    foreach ($params as $index=>$value) $query->bindValue($index + 1, $value, PDO::PARAM_STR);
    $query->bindValue(count($params) + 1, ($page - 1) * 10, PDO::PARAM_INT);
    $query->execute();
    return ['comments'=>$query->fetchAll(PDO::FETCH_ASSOC), 'page'=>$page, 'pages'=>$pages, 'total'=>$total, 'q'=>$keyword];
}

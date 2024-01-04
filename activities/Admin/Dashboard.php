<?php

namespace Admin;

use database\Database;

class Dashboard extends Admin
{

    public function index()
    {
        $db = new Database();
        //header sql codes
        $categoryCount = $db->select('SELECT COUNT(*) FROM categories')->fetch();
        $userCount = $db->select('SELECT COUNT(*) FROM users WHERE permission = "user"')->fetch();
        $adminCount = $db->select('SELECT COUNT(*) FROM users WHERE permission = "admin"')->fetch();
        $postCount = $db->select('SELECT COUNT(*) FROM posts')->fetch();
        $postsViews = $db->select('SELECT SUM(view) FROM posts')->fetch();
        $commentCount = $db->select('SELECT COUNT(*) FROM comments')->fetch();
        $commentUnseenCount = $db->select('SELECT COUNT(*) FROM comments WHERE status = "unseen"')->fetch();
        $commentApprovedCount = $db->select('SELECT COUNT(*) FROM comments WHERE status = "approved"')->fetch();


        //main sql codes
        $mostViewedPosts = $db->select('SELECT * FROM posts ORDER BY view DESC LIMIT 0,5')->fetchAll();

        $mostCommentedPosts = $db->select('SELECT posts.id, posts.title, COUNT(comments.post_id) AS comment_count FROM posts LEFT JOIN comments ON posts.id = comments.post_id GROUP BY posts.id ORDER BY comment_count DESC LIMIT 0,5')->fetchAll();

        $lastComments = $db->select('SELECT comments.id , comments.comment , comments.status, comments.post_id , users.username AS userName FROM comments LEFT JOIN users ON comments.user_id = users.id ORDER BY comments.created_at DESC LIMIT 0,5')->fetchAll();


        require_once(BASE_PATH . "/template/admin/dashboard/index.php");
    }
}

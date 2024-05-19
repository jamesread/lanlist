<?php

use libAllure\Form;
use libAllure\Session;

class FormEditBlogPost extends Form
{
    public function __construct($id, $isNewArticle = false)
    {
        $this->id = $id;

        Session::requirePriv('EDIT_BLOG');

        if (empty($isNewArticle)) {
            parent::__construct('editBlogPost', 'New post');
        } else {
            parent::__construct('editBlogPost', 'Edit post');
        }

        $post = $this->getPost();

        $this->addElement(Element::factory('textarea', 'title', 'Title', $post['title']));
        $this->addElement(Element::factory('textarea', 'content', 'Content', $post['content']));
        $this->addDefaultButtons();
    }

    private function getPost()
    {
        global $db;

        $sql = 'SELECT id, title, content FROM blogPosts WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->id);
        $stmt->execite();

        $article = $stmt->fetchRow();

        if (empty($article)) {
            throw new Exception('ARTICLE_NOT_FOUND');
        }

        return $article;
    }

    public function process()
    {
        global $db;

        $sql = 'UPDATE blostPosts SET content = :content, title = :title WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':content', $this->getElementValue('content'));
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();

        redirect('viewBlogPost.php?id=' . $this->id, 'Post edited.');
    }
}

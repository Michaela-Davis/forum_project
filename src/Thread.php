<?php
    Class Thread
    {
        private $post;
        private $category_id;
        private $user_id;
        private $post_title;
        private $category;
        private $id;

        function __construct($post, $category_id, $user_id, $post_title, $category, $id = null)
        {
            $this->post = $post;
            $this->category_id = $category_id;
            $this->user_id = $user_id;
            $this->post_title = $post_title;
            $this->category = $category;
            $this->id = $id;
        }

        function getPost()
        {
            return $this->post;
        }

        function setPost($post)
        {
            $this->post = $post;
        }

        function getCategoryId()
        {
            return $this->category_id;
        }

        function setCategoryId($category_id)
        {
            $this->category_id = $category_id;
        }

        function getUserId()
        {
            return $this->user_id;
        }

        function setUserId($user_id)
        {
            $this->user_id = $user_id;
        }

        function getPostTitle()
        {
            return $this->post_title;
        }

        function setPostTitle($post_title)
        {
            $this->post_title = $post_title;
        }
        
        function getCategory()
        {
            return $this->category;
        }

        function setCategory($category)
        {
            $this->category = $category;
        }

        function getId()
        {
            return $this->id;
        }

        function save()
        {
            $GLOBALS['DB']->exec("INSERT INTO threads (post, category_id, user_id, post_title, category) VALUES ('{$this->getPost()}', {$this->getCategoryId()}, {$this->getUserId()}, '{$this->getPostTitle()}', '{$this->getCategory()}');");
            $this->id = $GLOBALS['DB']->lastInsertId();
        }

        static function getAll()
        {
            $returned_threads = $GLOBALS['DB']->query("SELECT * FROM threads;");
            $threads = array();
            foreach($returned_threads as $thread) {
                $post = $thread['post'];
                $category_id = $thread['category_id'];
                $user_id = $thread['user_id'];
                $post_title = $thread['post_title'];
                $category = $thread['category'];
                $thread_id = $thread['thread_id'];
                $new_thread = new Thread($post, $category_id, $user_id, $post_title, $category, $thread_id);
                array_push($threads, $new_thread);
            }
            return $threads;
        }

        static function deleteAll()
        {
          $GLOBALS['DB']->exec("DELETE FROM threads;");
          $GLOBALS['DB']->exec("DELETE FROM threads_tags;");
          $GLOBALS['DB']->exec("DELETE FROM comments");
          $GLOBALS['DB']->exec("DELETE FROM comments_tags");
        }

        static function find($search_id)
        {
            $found_thread = null;
            $threads = Thread::getAll();
            foreach($threads as $thread) {
                $thread_id = $thread->getId();
                if ($thread_id == $search_id) {
                  $found_thread = $thread;
                }
            }
            return $found_thread;
        }

        // static function findByCategoryId($search_id)
        // {
        //     // get category from category table where the category.category_id = thread.category_id
        //     $query = $GLOBALS['DB']->query("SELECT category FROM categories WHERE category.category_id = thread.category_id");
        //
        //     $found_category = null;
        //
        //     foreach($query as $category) {
        //         $category_name = $category->getCategory();
        //         if ($category_name == $search_id) {
        //           $found_category = $category;
        //         }
        //     }
        //     return $found_category;
        // }



        function delete()
        {
            $GLOBALS['DB']->exec("DELETE FROM threads WHERE thread_id = {$this->getId()};");
            $GLOBALS['DB']->exec("DELETE FROM threads_tags WHERE thread_id = {$this->getId()};");
            $GLOBALS['DB']->exec("DELETE FROM comments_tags JOIN comments ON (threads.thread_id = comments.thread_id) JOIN comments_tags ON (comments.comment_id = comments_tags.comment_id) WHERE thread_id = {$this->getId()};");
            $GLOBALS['DB']->exec("DELETE FROM comments WHERE thread_id = {$this->getId()};");
        }

        function addTag($tag)
        {
            $GLOBALS['DB']->exec("INSERT INTO threads_tags (thread_id, tag_id) VALUES ({$this->getId()}, {$tag->getId()})");
        }

        function getTags()
        {
            $returned_tags = $GLOBALS['DB']->query("SELECT tags.* FROM threads
            JOIN threads_tags ON (threads_tags.thread_id = threads.thread_id)
            JOIN tags ON (tags.tag_id = threads_tags.tag_id)
            WHERE threads.thread_id = {$this->getId()};");
            $tags = array();
            foreach($returned_tags as $tag) {
                $tag_name = $tag['tag'];
                $id = $tag['tag_id'];
                $new_tag = new Tag($tag_name, $id);
                array_push($tags, $new_tag);
                }
            return $tags;
        }
        
        function getUser(){
          var_dump($this->getId());
            $returned_users = $GLOBALS['DB']->query("SELECT users.username from threads join users ON (threads.user_id = users.user_id) where users.user_id = {$this->getUserId()};");
            $users = null;
            foreach($returned_users as $user) {
              var_dump("user");
              var_dump($user);
                $user_name = $user[0];
                $users =  $user_name;
                }
            return $users;
        }

        function getComments()
        {
            $returned_comments = $GLOBALS['DB']->query("SELECT * FROM comments WHERE thread_id = {$this->getId()} ORDER BY score DESC;");
            $comments = array();
            foreach($returned_comments as $comment) {
                $user = $comment['user_id'];
                $username = User::find($user)->getUsername();
                $comment_text = $comment['comment'];
                $parent_id = $comment['parent_id'];
                $score = $comment['score'];
                $post_time = $comment['post_time'];
                $init_comment_id = $comment['init_comment_id'];
                $thread_id = $comment['thread_id'];
                $comment_id = $comment['comment_id'];
                if($parent_id==null){
                  $parent_id = "false";
                }
                $this_comment = Comment::find($comment_id);
                $tags_array = $this_comment->getTags();
                $tags = "";
                foreach($tags_array as $tag){
                  $tags = $tags . " " . $tag->getTag();
                }
                $new_comment = '{"username":"' . $username . '", "user_id":"' . $user . '", "comment":"' . $comment_text . '", "parent_id":"' . $parent_id . '", "score":"' . $score . '", "post_time":"' . $post_time . '", "thread_id":"' . $thread_id . '", "comment_id":"' . $comment_id . '", "tags":"' . $tags . '"}';
                array_push($comments, $new_comment);
            }
            return $comments;
        }
        
        function update($post)
        {
            $GLOBALS['DB']->exec("UPDATE threads SET post = '{$post}' WHERE thread_id = {$this->getId()};");
            $this->setPost($post);
        }
        
        static function searchFor($search_term)
        {
            $matches = array();
            // $search_term = explode(" ", strtolower($search_term));
        
            $query = $GLOBALS['DB']->query("SELECT * FROM threads WHERE post_title LIKE '%$search_term%' OR post LIKE '%$search_term%' OR category LIKE '%$search_term%';");
            
            foreach($query as $thread) {
                $post = $thread['post'];
                $category_id = $thread['category_id'];
                $user_id = $thread['user_id'];
                $post_title = $thread['post_title'];
                $category = $thread['category'];
                $thread_id = $thread['thread_id'];
                $new_thread = new Thread($post, $category_id, $user_id, $post_title, $category, $thread_id);
                array_push($matches, $new_thread);
            }
            return $matches;
        }

    }

?>

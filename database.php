<?php

require_once 'config.php';

class Database
{
    
    /***** STATIC *****/
    
    
    // How many items to display in homepage
    const HOMEPAGE_RESULTS = 50;
    
    protected static function get_random_string ($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $characters_length = strlen ($characters);
        
        $hash_id = '';
        
        for ($i = 0; $i < $length; $i++)
            $hash_id .= $characters[mt_rand (0, $characters_length - 1)];
        
        return $hash_id;
    }
    
    
    /***** INSTANCE *****/
    
    
    // Reference to a database connection
    protected $database;
    
    function __construct ()
    {
        $this->database = NULL;
    }
    
    function connect ()
    {
        try {
            
            $this->database = new PDO (
                Config::$DATABASE['dsn'],
                Config::$DATABASE['username'],
                Config::$DATABASE['password']);
                
            $this->database->setAttribute (
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION);
            
            return true;
            
        } catch(PDOException $e) {
            
            return false;
            
        }
    }
    
    function disconnect ()
    {
        $this->database = NULL;
    }
    
    /**
     * Retrieve a user
     */
    function get_user ($username)
    {
        $user = NULL;
        
        if (is_null ($this->database))
            return $user;
        
        $query = $this->database->prepare (
            'SELECT * ' .
            'FROM `user` ' .
            'WHERE `username` = ?');
            
        $query->execute (array ($username));
            
        $user = $query->fetch(PDO::FETCH_ASSOC);
        
        return $user;
    }
    
    /**
     * Check if user exists
     */
    function user_exists ($username)
    {
        $user = self::get_user ($username);
        
        return !is_null ($user) && !empty ($user);
    }
    
    /**
     * Check user login credentials
     * 
     * @return NULL if bad credentials, otherwise return the user
     */
    function check_user_credentials ($username, $password)
    {
        try {
            
            $this->database->beginTransaction();
            $user = NULL;
            
            if (is_null ($this->database))
                return $user;
            
            $query = $this->database->prepare (
                'SELECT * ' .
                'FROM `user`' .
                'WHERE ' .
                    '`username` = ? AND ' .
                    '`password` = SHA2(CONCAT(?, `salt`), 512) AND ' .
                    '`isActive` = 1');
                
            $query->execute (array ($username, $password));
            
            $user = $query->fetch (PDO::FETCH_ASSOC);
            
            /* This bit of code is only needed to maintain interoperability
            * with the old version of kodapost, such that users are not
            * required to create a new account.
            * 
            * Once all hash/salt have been updated for all users, this code can
            * be safely removed.
            * 
            * The old credentials are OK; update with a new
            * hash/salt to update users with the new kodapost!
            */
            if (!is_null ($user) && !empty ($user) && array_key_exists ('salt', $user) && $user['salt'] == '')
            {
                // Create a salt for user password
                $salt = self::get_random_string (16);
                
                // Update hash/salt for the user
                $query = $this->database->prepare (
                    'UPDATE `user`' .
                    'SET `password` = SHA2(?, 512), `salt` = ? ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($password . $salt, $salt, $user['id']));
                
                // Refetch the user again
                $user = self::get_user ($username);
            }
            /////////////////////////////////////////////////////////////////////
            
            $this->database->commit ();
            
            return $user;
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
            return NULL;
            
        }
    }
    
    /**
     * Retrieve a $user from database using remember_me token
     */
    function get_remember_me ($token)
    {
        $user = array();
        
        if (is_null ($this->database))
            return $user;
        
        $query = $this->database->prepare(
            'SELECT U.* ' .
            'FROM `user` AS U ' .
            'JOIN `remember_me` AS R ON R.`userId` = U.`id`' .
            'WHERE R.`token` = ? AND R.`expires` > NOW()');
            
        $query->execute (array (hash ('sha512', $token)));
            
        $user = $query->fetch (PDO::FETCH_ASSOC);
            
        return $user;
    }
    
    /**
     * Set a new remember_me token to database
     * 
     * @return secret token (cleartext)
     */
    function set_remember_me ($user_id)
    {
        /* Set remember me token.
         * The cleartext token is stored as a user cookie, while in our
         * database we store hash(token).
         */
        
        // Delete all previous remember_me tokens for $user
        self::delete_remember_me ($user_id);
            
        // Create a new secret token
        $token = self::get_random_string (128);
        
        $query = $this->database->prepare (
            'INSERT INTO `remember_me` (`token`, `userId`, `expires`)' .
            'VALUES (?, ?, NOW() + INTERVAL 30 DAY)');
            
        $query->execute (array (hash('sha512', $token), $user_id));
        
        return $token;
    }
    
    /**
     * Delete $user "remember_me" token
     */
    function delete_remember_me ($user_id)
    {
        // Delete all previous remember_me tokens for $user
        $query = $this->database->prepare(
            'DELETE FROM `remember_me`' .
            'WHERE `userId` = ?');
            
        $query->execute (array ($user_id));
    }
    
    /**
     * Retrieve a post
     */
    function get_post ($hash_id)
    {
        $post = array();
        
        if (is_null($this->database))
            return $post;
        
        $query = $this->database->prepare(
            'SELECT P.*, U.`username` ' .
            'FROM `post` AS P ' .
            'JOIN `user` AS U ON P.`userId` = U.`id` ' .
            'WHERE P.`hashId` = ?');
        
        $query->execute (array ($hash_id));
        
        $post = $query->fetch(PDO::FETCH_ASSOC);
        
        return $post;
    }
    
    /**
     * Retrieve the user of a specific post
     */
    function get_post_op ($post_hash_id)
    {
        $user = array();
        
        if (is_null ($this->database))
            return $user;
        
        $query = $this->database->prepare(
            'SELECT U.* ' .
            'FROM `user` AS U ' .
            'JOIN `post` AS P ON P.`userId` = U.`id` ' .
            'WHERE P.`hashId` = ?');
            
        $query->execute (array ($post_hash_id));
        
        $user = $query->fetch (PDO::FETCH_ASSOC);
        
        return $user;
    }
    
    /**
     * Retrieve all comments for a specific post
     */
    function get_post_comments ($post_id)
    {
        $comments = array ();
        
        if (is_null ($this->database))
            return $comments;
        
        $query = $this->database->prepare (
            'SELECT C.*, U.`username`' .
            'FROM `comment` AS C ' .
            'JOIN `user` AS U ON C.`userId` = U.`id`' .
            'WHERE C.`postId` = ? ' .
            'ORDER BY C.`vote` DESC, C.`created` ASC');
        
        $query->execute (array ($post_id));
        
        $comments = $query->fetchAll (PDO::FETCH_ASSOC);
        
        // Group comments by parentId
        $comments_group = array();
        
        foreach ($comments as $comment)
            if (is_null ($comment['parentId']))
            {
                $comments_group[0][] = $comment;
            }
            else
            {
                $comments_group[$comment['parentId']][] = $comment;
            }
            
        
        return $comments_group;
    }
    
    /**
     * Retrieve a single comment
     */
    function get_comment ($hash_id)
    {
        $comment = array ();
        
        if (is_null ($this->database))
            return $comment;
        
        $query = $this->database->prepare (
            'SELECT ' .
                'C.*,' .
                'P.`hashId` AS `postHashId`,' .
                'P.`title` AS `postTitle`,' .
                'U.`username`' .
            'FROM `comment` AS C ' .
            'JOIN `user` AS U ON C.`userId` = U.`id` ' .
            'JOIN `post` AS P ON P.`id` = C.`postId` ' .
            'WHERE C.`hashId` = ?');
            
        $query->execute (array($hash_id));
        
        $comment = $query->fetch (PDO::FETCH_ASSOC);
        
        return $comment;
    }
    
    /**
     * Retrieve the user of a specific comment
     */
    function get_comment_op ($comment_hash_id)
    {
        $user = array();
        
        if (is_null ($this->database))
            return $user;
        
        $query = $this->database->prepare(
            'SELECT U.* ' .
            'FROM `user` AS U ' .
            'JOIN `comment` AS C ON C.`userId` = U.`id` ' .
            'WHERE C.`hashId` = ?');
            
        $query->execute (array ($comment_hash_id));
        
        $user = $query->fetch (PDO::FETCH_ASSOC);
        
        return $user;
    }
    
    /**
     * Return the number of unread messages
     */
    function count_unread_messages ($user_id)
    {
        if (is_null ($this->database))
            return 0;
        
        $query = $this->database->prepare (
            'SELECT COUNT(1)' .
            'FROM `comment`' .
            'WHERE `parentUserId` = :user_id AND `userId` != :user_id AND `read` = 0');
        
        $query->execute (array ('user_id' => $user_id));
        
        return $query->fetchColumn();
    }
    
    /**
     * Get posts by rating (for homepage)
     */
    function get_hot_posts ($page = 0)
    {
        $submissions = array();
        $page = intval ($page);
        
        if (is_null ($this->database))
            return $submissions;
        
        if ($page < 0)
            $page = 0;
        
        $query = $this->database->prepare (
            'SELECT P.*, U.`username`' .
            'FROM `post` AS P ' .
            'JOIN `user` AS U ON P.`userId` = U.`id`' .
            'ORDER BY P.`dateCreated` DESC, P.`vote` DESC, P.`commentsCount` DESC ' .
            'LIMIT :limit OFFSET :offset');
        
        $query->bindValue (':limit', Database::HOMEPAGE_RESULTS, PDO::PARAM_INT);
        $query->bindValue (':offset', $page * Database::HOMEPAGE_RESULTS, PDO::PARAM_INT);
        
        $query->execute ();
        
        $submissions = $query->fetchAll (PDO::FETCH_ASSOC);
        
        return $submissions;
    }
    
    /**
     * Get posts by date (for homepage)
     */
    function get_new_posts ($page = 0)
    {
        $submissions = array();
        $page = intval ($page);
        
        if (is_null ($this->database))
            return $submissions;
        
        if ($page < 0)
            $page = 0;
        
        $query = $this->database->prepare (
            'SELECT P.*, U.`username`' .
            'FROM `post` AS P ' .
            'JOIN `user` AS U ON P.`userId` = U.`id`' .
            'ORDER BY P.`created` DESC ' .
            'LIMIT :limit OFFSET :offset');
        
        $query->bindValue (':limit', Database::HOMEPAGE_RESULTS, PDO::PARAM_INT);
        $query->bindValue (':offset', $page * Database::HOMEPAGE_RESULTS, PDO::PARAM_INT);
        
        $query->execute ();
            
        $submissions = $query->fetchAll (PDO::FETCH_ASSOC);
        
        return $submissions;
    }
    
    /**
     * Get user posts (used to show user activity)
     */
    function get_user_posts ($user_id)
    {
        $posts = array();
        
        if (is_null ($this->database))
            return $posts;
        
        $query = $this->database->prepare (
            'SELECT * ' .
            'FROM `post`' .
            'WHERE `userId` = ? ' .
            'ORDER BY `created` DESC ' .
            'LIMIT 50');
        
        $query->execute (array($user_id));
        
        $posts = $query->fetchAll(PDO::FETCH_ASSOC);
            
        return $posts;
    }
    
    /**
     * Get user comments (used to show user activity)
     */
    function get_user_comments ($user_id)
    {
        $comments = array();
        
        if (is_null ($this->database))
            return $comments;
        
        $query = $this->database->prepare (
            'SELECT ' .
                'C.*,' .
                'P.`title` AS `postTitle`,' .
                'P.`hashId` AS `postHashId`' .
            'FROM `comment` AS C ' .
            'JOIN `post` AS P ON P.`id` = C.`postId`' .
            'WHERE C.`userId` = ? ' .
            'ORDER BY C.`created` DESC ' .
            'LIMIT 50');
        
        $query->execute (array($user_id));
        
        $comments = $query->fetchAll(PDO::FETCH_ASSOC);
        
        return $comments;
    }
    
    /**
     * Get user replies (used to show user activity)
     */
    function get_user_replies ($user_id)
    {
        $replies = array();
        
        if (is_null ($this->database))
            return $replies;
        
        $query = $this->database->prepare (
            'SELECT ' .
                'C.*,' .
                'P.`title` AS `postTitle`,' .
                'P.`hashId` AS `postHashId`,' .
                'U.`username` AS `username`' .
            'FROM `comment` AS C ' .
            'JOIN `post` AS P ON P.`id` = C.`postId`' .
            'JOIN `user` AS U ON U.`id` = C.`userId`' .
            'WHERE C.`parentUserId` = :user_id AND C.`userId` != :user_id ' .
            'ORDER BY C.`created` DESC ' .
            'LIMIT 50');
            
        $query->execute (array ('user_id' => $user_id));
            
        $replies = $query->fetchAll(PDO::FETCH_ASSOC);
            
        return $replies;
    }
    
    /**
     * Set user replies as read
     */
    function set_replies_as_read ($user_id)
    {
        $query = $this->database->prepare (
            'UPDATE `comment`' .
            'SET `read` = 1 ' .
            'WHERE `parentUserId` = ? AND `read` = 0');
            
        $query->execute (array ($user_id));
    }
    
    /**
     * Retrieve a list of votes for a range of posts.
     * 
     * @param posts_id list of IDs (eg. "2,4,5").
     *                  NOTE: Because arrays can't be used with PDO, $posts_id
     *                  is a string that's concatenated to the SQL query. For
     *                  this reason is the responsibility of the caller to
     *                  check that $posts_id is a valid string of integers
     *                  separated by commans (beware of SQL injection).
     */
    function get_posts_votes ($posts_id, $user_id)
    {
        $votes = array();
        
        if (is_null ($this->database) || is_null ($posts_id) || is_null ($user_id))
            return $votes;
        
        // Run a test anyway to make sure $posts_id is a valid string
        $posts_id_array = explode (',', $posts_id);
        
        foreach ($posts_id_array as $post_id)
            if (!is_numeric ($post_id))
                return $votes;
        
        // Retrieve the votes
        $query = $this->database->prepare (
            'SELECT * ' .
            'FROM `vote_post`' .
            'WHERE `postId` IN(' . $posts_id . ') AND `userId` = ?');
        
        $query->execute (array ($user_id));
        
        $votes = $query->fetchAll(PDO::FETCH_ASSOC);
        
        // Create an array of votes with `postId` as key
        $sorted_votes = array();
        
        foreach ($votes as $vote)
            $sorted_votes[$vote['postId']] = $vote;
        
        return $sorted_votes;
    }
    
    /**
     * Retrieve a list of votes for a range of comments.
     * 
     * @param comments_id list of IDs (eg. "2,4,5").
     *                  NOTE: Because arrays can't be used with PDO, $comments_id
     *                  is a string that's concatenated to the SQL query. For
     *                  this reason is the responsibility of the caller to
     *                  check that $comments_id is a valid string of integers
     *                  separated by commans (beware of SQL injection).
     */
    function get_comments_votes ($comments_id, $user_id)
    {
        $votes = array();
        
        if (is_null ($this->database) || is_null ($comments_id) || is_null ($user_id))
            return $votes;
        
        // Run a test anyway to make sure $posts_id is a valid string
        $comments_id_array = explode (',', $comments_id);
        
        foreach ($comments_id_array as $comment_id)
            if (!is_numeric ($comment_id))
                return $votes;
            
        // Retrieve the votes
        $query = $this->database->prepare (
            'SELECT * ' .
            'FROM `vote_comment`' .
            'WHERE `commentId` IN(' . $comments_id . ') AND `userId` = ?');
            
        $query->execute (array ($user_id));
            
        $votes = $query->fetchAll (PDO::FETCH_ASSOC);
            
        // Create an array of votes with `commentId` as key
        $sorted_votes = array();
        
        foreach ($votes as $vote)
            $sorted_votes[$vote['commentId']] = $vote;
        
        return $sorted_votes;
    }
    
    /**
     * Create new user account
     */
    function new_user ($username, $password)
    {
        $user = NULL;
        
        try {
            
            // Create a hash_id for the new post
            $new_hash_id = self::get_random_string();
            
            // Create a salt for user password
            $salt = self::get_random_string (16);
            
            $this->database->beginTransaction();
            
            // Create the new user
            $query = $this->database->prepare(
                'INSERT INTO `user` (`hashId`, `isActive`, `password`, `registered`, `salt`, `username`)' .
                'VALUES (?, 1, SHA2(?, 512), NOW(), ?, ?)');
                
            $query->execute(array($new_hash_id, $password . $salt, $salt, $username));
            
            // Retrieve the new user
            $user = self::get_user ($username);
            
            $this->database->commit();
            
            return $user;
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
        }
    }
    
    /**
     * Submit a new post/link
     */
    function new_post ($title, $link, $text, $user_id)
    {
        // Create a hash_id for the new post
        $new_hash_id = self::get_random_string();
        
        try {
            
            $this->database->beginTransaction();
            
            // Create the new post
            $query = $this->database->prepare(
                'INSERT INTO `post` (`hashId`, `created`, `dateCreated`, `title`, `link`, `text`, `vote`, `commentsCount`, `userId`)' .
                'VALUES (?, NOW(), CURDATE(), ?, ?, ?, 0, 0, ?)');
                
            $query->execute(array($new_hash_id, $title, $link, $text, $user_id));
            
            // Retrieve the id of the new post
            $post_id = $this->database->lastInsertId();
            
            $this->database->commit();
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
            return NULL;
            
        }
        
        // Automatically upvote this post (start from +1)
        self::upvote_post ($new_hash_id, $user_id);
        
        return $new_hash_id;
    }
    
    /**
     * Submit a new comment to a post
     */
    function new_comment ($comment, $post_hash_id, $user_id)
    {
        $new_hash_id = NULL;
        
        try {
            
            $new_hash_id = self::get_random_string();
            
            $this->database->beginTransaction();
            
            // Retrieve the post
            $query = $this->database->prepare(
                'SELECT * ' .
                'FROM `post`' .
                'WHERE `hashId` = ?');
                
            $query->execute(array($post_hash_id));
            
            $post = $query->fetch(PDO::FETCH_ASSOC);
            
            // Add new comment
            $query = $this->database->prepare(
                'INSERT INTO `comment` (`hashId`, `created`, `dateCreated`, `read`, `text`, `vote`, `parentId`, `parentUserId`, `postId`, `userId`)' .
                'VALUES (?, NOW(), CURDATE(), 0, ?, 1, ?, ?, ?, ?)');
                
            $query->execute(array($new_hash_id, $comment, NULL, $post['userId'], $post['id'], $user_id));
            
            $comment_id = $this->database->lastInsertId();
            
            // Add vote
            $query = $this->database->prepare(
                'INSERT INTO `vote_comment` (`vote`, `datetime`, `commentId`, `userId`)' .
                'VALUES (1, NOW(), ?, ?)');
                
            $query->execute(array($comment_id, $user_id));
            
            // Increase comments count for post
            $query = $this->database->prepare (
                'UPDATE `post`' .
                'SET `commentsCount` = `commentsCount` + 1 ' .
                'WHERE `hashId` = ?');
            
            $query->execute (array ($post_hash_id));
            
            $this->database->commit();

            return $new_hash_id;
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
            return NULL;
            
        }
    }
    
    /**
     * Submit a new reply to a comment
     */
    function new_reply ($comment, $parent_comment_hash_id, $user_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Retrieve the parent comment
            $query = $this->database->prepare (
                'SELECT * ' .
                'FROM `comment`' .
                'WHERE `hashId` = ?');
                
            $query->execute (array ($parent_comment_hash_id));
            
            $parent_comment = $query->fetch (PDO::FETCH_ASSOC);
            
            // Retrieve the post
            $query = $this->database->prepare (
                'SELECT * ' .
                'FROM `post`' .
                'WHERE `id` = ?');
                
            $query->execute (array ($parent_comment['postId']));
            
            $post = $query->fetch(PDO::FETCH_ASSOC);
            
            // Add the new comment
            $comment_hash_id = self::get_random_string();
            
            $query = $this->database->prepare (
                'INSERT INTO `comment` (`hashId`, `created`, `dateCreated`, `read`, `text`, `vote`, `parentId`, `parentUserId`, `postId`, `userId`)' .
                'VALUES (?, NOW(), CURDATE(), 0, ?, 1, ?, ?, ?, ?)');
            
            $query->execute (array ($comment_hash_id, $comment, $parent_comment['id'], $parent_comment['userId'], $post['id'], $user_id));
            
            $comment_id = $this->database->lastInsertId();
            
            // Add first vote for user comment
            $query = $this->database->prepare (
                'INSERT INTO `vote_comment` (`vote`, `datetime`, `commentId`, `userId`)' .
                'VALUES (1, NOW(), ?, ?)');
                
            $query->execute (array ($comment_id, $user_id));
            
            // Increase comments count for post
            $query = $this->database->prepare (
                'UPDATE `post`' .
                'SET `commentsCount` = `commentsCount` + 1 ' .
                'WHERE `hashId` = ?');
                
            $query->execute (array ($post['hashId']));
            
            $this->database->commit ();
            
            // Return the hash_id of both post and comment
            return array (
                'post'    => $post['hashId'],
                'comment' => $comment_hash_id);
                
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
            return NULL;
            
        }
    }
    
    /**
     * Update a post text
     */
    function edit_post ($title, $link, $text, $post_hash_id, $user_id)
    {
        $query = $this->database->prepare (
            'UPDATE `post`' .
            'SET ' .
                '`title` = ?, ' .
                '`link` = ?, ' .
                '`text` = ? ' .
            'WHERE `hashId` = ? AND `userId` = ?');
        
        $query->execute ([$title, $link, $text, $post_hash_id, $user_id]);
        
        $affected_rows = $query->rowCount();
        
        return $affected_rows;
    }
    
    /**
     * Update a comment text
     */
    function edit_comment ($text, $comment_hash_id, $user_id)
    {
        $query = $this->database->prepare (
            'UPDATE `comment`' .
            'SET `text` = ? ' .
            'WHERE `hashId` = ? AND `userId` = ?');
            
            $query->execute (array ($text, $comment_hash_id, $user_id));
            
        $affected_rows = $query->rowCount();
        
        return $affected_rows;
    }
    
    /**
     * Update user information
     */
    function edit_user ($about, $email, $email_notifications, $user_id)
    {
        $query = $this->database->prepare (
            'UPDATE `user`' .
            'SET `about` = :about, `email` = :email, `email_notifications` = :email_notifications ' .
            'WHERE `id` = :user_id');
        
        $query->bindValue (':about', $about, PDO::PARAM_STR);
        $query->bindValue (':user_id', $user_id, PDO::PARAM_INT);
        $query->bindValue (':email_notifications', $email_notifications, PDO::PARAM_INT);
        
        if (NULL == $email || '' == $email)
            $query->bindValue (':email', NULL, PDO::PARAM_NULL);
        else
            $query->bindValue (':email', $email, PDO::PARAM_STR);
        
        $query->execute ();
    }
    
    /**
     * Tell if a user has voted a post
     */
    function voted_post ($post_id, $user_id)
    {
        if (is_null($this->database))
            return false;
        
        $query = $this->database->prepare(
            'SELECT * ' .
            'FROM `vote_post`' .
            'WHERE `postId` = ? and `userId` = ?');
            
        $query->execute (array ($post_id, $user_id));
        
        $vote = $query->fetch (PDO::FETCH_ASSOC);
        
        if (is_null ($vote) || empty ($vote))
            return false;
        
        return $vote;
    }
    
    /**
     * Tell if a user has voted a comment
     */
    function voted_comment ($comment_id, $user_id)
    {
        if (is_null($this->database))
            return false;
        
        $query = $this->database->prepare(
            'SELECT * ' .
            'FROM `vote_comment`' .
            'WHERE `commentId` = ? and `userId` = ?');
            
            $query->execute (array ($comment_id, $user_id));
            
        $vote = $query->fetch (PDO::FETCH_ASSOC);
            
        if (is_null ($vote) || empty ($vote))
            return false;
            
        return $vote;
    }
    
    /**
     * Upvote a post
     */
    function upvote_post ($post_hash_id, $user_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Get the post
            $post = self::get_post ($post_hash_id);
            
            // Already voted?
            $vote = self::voted_post ($post['id'], $user_id);
            
            if (false == $vote)
            {
                // Cast upvote
                $query = $this->database->prepare(
                    'INSERT INTO `vote_post` (`vote`, `datetime`, `postId`, `userId`)' .
                    'VALUES (1, NOW(), ?, ?)');
                
                $query->execute (array ($post['id'], $user_id));
                
                // Add +1 to post
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` + 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
                
            } elseif ($vote['vote'] == 1) {
                // Already upvoted before. Remove upvote.
                
                $query = $this->database->prepare(
                    'DELETE FROM `vote_post`' .
                    'WHERE `postId` = ? AND `userId` = ?');
                    
                $query->execute (array ($post['id'], $user_id));
                
                // Remove upvote from post
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` - 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
                    
            } elseif ($vote['vote'] == -1) {
                // Already downvoted before. Change to upvote.
                
                $query = $this->database->prepare(
                    'UPDATE `vote_post`' .
                    'SET `vote` = 1 ' .
                    'WHERE `postId` = ? AND `userId` = ?');
                    
                $query->execute (array ($post['id'], $user_id));
                    
                /* Update post vote count
                 * +2 because of the previous downvote
                 */
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` + 2 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
            }
            
            $this->database->commit ();
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
        }
    }
    
    /**
     * Downvote a post
     */
    function downvote_post ($post_hash_id, $user_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Get the post
            $post = self::get_post ($post_hash_id);
            
            // Already voted?
            $vote = self::voted_post ($post['id'], $user_id);
            
            if (false == $vote)
            {
                // Cast downvote
                $query = $this->database->prepare(
                    'INSERT INTO `vote_post` (`vote`, `datetime`, `postId`, `userId`)' .
                    'VALUES (-1, NOW(), ?, ?)');
                    
                $query->execute (array ($post['id'], $user_id));
                    
                // Add -1 to post
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` - 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
                        
            } elseif ($vote['vote'] == -1) {
                // Already downvoted before. Remove downvote.
                
                $query = $this->database->prepare(
                    'DELETE FROM `vote_post`' .
                    'WHERE `postId` = ? AND `userId` = ?');
                    
                $query->execute (array ($post['id'], $user_id));
                    
                // Remove downvote from post
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` + 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
                        
            } elseif ($vote['vote'] == 1) {
                // Already upvoted before. Change to downvote.
                
                $query = $this->database->prepare(
                    'UPDATE `vote_post`' .
                    'SET `vote` = -1 ' .
                    'WHERE `postId` = ? AND `userId` = ?');
                    
                $query->execute (array ($post['id'], $user_id));
                    
                /* Update post vote count
                 * -2 because of the previous upvote
                 */
                $query = $this->database->prepare (
                    'UPDATE `post`' .
                    'SET `vote` = `vote` - 2 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($post['id']));
            }
            
            $this->database->commit ();
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
        }
    }
    
    /**
     * Upvote a comment
     */
    function upvote_comment ($comment_hash_id, $user_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Get the comment
            $comment = self::get_comment ($comment_hash_id);
            
            // Already voted?
            $vote = self::voted_comment ($comment['id'], $user_id);
            
            if (false == $vote)
            {
                // Cast upvote
                $query = $this->database->prepare(
                    'INSERT INTO `vote_comment` (`vote`, `datetime`, `commentId`, `userId`)' .
                    'VALUES (1, NOW(), ?, ?)');
                    
                $query->execute (array ($comment['id'], $user_id));
                    
                // Add +1 to comment
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` + 1 ' .
                    'WHERE `id` = ?');
                
                $query->execute (array ($comment['id']));
                        
            } elseif ($vote['vote'] == 1) {
                // Already upvoted before. Remove upvote.
                
                $query = $this->database->prepare(
                    'DELETE FROM `vote_comment`' .
                    'WHERE `commentId` = ? AND `userId` = ?');
                    
                $query->execute (array ($comment['id'], $user_id));
                    
                // Remove upvote from comment
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` - 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($comment['id']));
                        
            } elseif ($vote['vote'] == -1) {
                // Already downvoted before. Change to upvote.
                
                $query = $this->database->prepare(
                    'UPDATE `vote_comment`' .
                    'SET `vote` = 1 ' .
                    'WHERE `commentId` = ? AND `userId` = ?');
                    
                $query->execute (array ($comment['id'], $user_id));
                    
                /* Update comment vote count
                 * +2 because of the previous downvote
                 */
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` + 2 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($comment['id']));
            }
            
            $this->database->commit ();
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
        }
    }
    
    /**
     * Downvote a comment
     */
    function downvote_comment ($comment_hash_id, $user_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Get the comment
            $comment = self::get_comment ($comment_hash_id);
            
            // Already voted?
            $vote = self::voted_comment ($comment['id'], $user_id);
            
            if (false == $vote)
            {
                // Cast downvote
                $query = $this->database->prepare(
                    'INSERT INTO `vote_comment` (`vote`, `datetime`, `commentId`, `userId`)' .
                    'VALUES (-1, NOW(), ?, ?)');
                    
                $query->execute (array ($comment['id'], $user_id));
                    
                // Add -1 to comment
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` - 1 ' .
                    'WHERE `id` = ?');
                
                $query->execute (array ($comment['id']));
                        
            } elseif ($vote['vote'] == -1) {
                // Already downvoted before. Remove downvote.
                
                $query = $this->database->prepare(
                    'DELETE FROM `vote_comment`' .
                    'WHERE `commentId` = ? AND `userId` = ?');
                    
                $query->execute (array ($comment['id'], $user_id));
                    
                // Remove downvote from comment
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` + 1 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($comment['id']));
                        
            } elseif ($vote['vote'] == 1) {
                // Already upvoted before. Change to downvote.
                
                $query = $this->database->prepare(
                    'UPDATE `vote_comment`' .
                    'SET `vote` = -1 ' .
                    'WHERE `commentId` = ? AND `userId` = ?');
                    
                $query->execute (array ($comment['id'], $user_id));
                
                /* Update comment vote count
                 * -2 because of the previous upvote
                 */
                $query = $this->database->prepare (
                    'UPDATE `comment`' .
                    'SET `vote` = `vote` - 2 ' .
                    'WHERE `id` = ?');
                    
                $query->execute (array ($comment['id']));
            }
            
            $this->database->commit ();
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
        }
    }
    
    /**
     * Reset a user password. This function adds a new passwordResetCode
     * Lately, the new password is updated with password_reset_validate()
     */
    function password_reset ($user_hash_id)
    {
        try {
            
            $this->database->beginTransaction();
            
            // Generate a new secret token (used for validation)
            $token = self::get_random_string (32);
            
            /* Add the new secret token to the database.
             * Check that the last resetToken wasn't set less than 5 minutes
             * ago. This is to prevent spam from other users who might have
             * inserted the same email.
             * 
             * 1435 = 24h x 60' - 5'
             */
            $query = $this->database->prepare (
                'UPDATE `user`' .
                'SET `passwordResetToken` = ?, `passwordResetTokenExpire` = NOW() + INTERVAL 1 DAY ' .
                'WHERE' .
                    '`hashId` = ? AND ' .
                    '(`passwordResetTokenExpire` IS NULL OR TIMESTAMPDIFF(MINUTE, NOW(), `passwordResetTokenExpire`) < 1435)');
                
            $query->execute (array ($token, $user_hash_id));
            
            $affected_rows = $query->rowCount ();
            
            $this->database->commit ();
            
            return $affected_rows ? $token : NULL;
            
        } catch(PDOException $ex) {
            
            $this->database->rollBack();
            
            return NULL;
            
        }
    }
    
    /**
     * Check if a token (sent by email for password reset) is valid
     */
    function password_reset_validate ($token, $new_password)
    {
        if (is_null ($this->database))
            return false;
        
        $query = $this->database->prepare (
            'UPDATE `user`' .
            'SET `password` = SHA2(CONCAT(?, `salt`), 512), `passwordResetToken` = NULL, `passwordResetTokenExpire` = NULL ' .
            'WHERE `passwordResetToken` = ? AND `passwordResetTokenExpire` > NOW()');
            
        $query->execute (array ($new_password, $token));
        
        return true;
    }
    
    /**
     * Search database
     * 
     * TODO: When MySQL will be updated to v5.6, add fulltext search
     *       with scores for better results. InnoDB doesn't support
     *       fulltext search with older versions.
     */
    function search ($search_query)
    {
        if (is_null ($this->database))
            return false;
        
        $search_query = trim ($search_query);
        
        if (strlen ($search_query) == 0)
            return false;
        
        $search_query = str_ireplace (' ', '%', $search_query);
        
        $query = $this->database->prepare (
            'SELECT P.*, U.`username` ' .
            'FROM `post` AS P ' .
            'JOIN `user` AS U ON P.`userId` = U.`id`' .
            'WHERE P.`title` LIKE ? ' .
            'ORDER BY P.`created` DESC ' .
            'LIMIT 100');
        
        $query->execute (['%' . $search_query . '%']);
        
        $results = $query->fetchAll (PDO::FETCH_ASSOC);
        
        return $results;
    }
}

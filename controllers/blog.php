<?php
class Blog extends Controller {

    /** This method checks if a category ID is provided in the URL parameters.
     * If it is present, it fetches the corresponding category and the list of posts associated with that category. It then
     * fetches all published posts. If no category ID is provided, it fetches all published posts.
     * It then maps the posts to their users and categories and sends the resulting data to be used in the view.
     */
    public function index($f3) {    
        if ($f3->exists('PARAMS.3')) {
            $categoryid = $f3->get('PARAMS.3');
            $category = $this->Model->Categories->fetch($categoryid);
            $postlist = array_values($this->Model->Post_Categories->fetchList(array('id','post_id'),array('category_id' => $categoryid)));
            
            $posts = $this->Model->Posts->fetchAll(array('id' => $postlist, 'published' => 'IS NOT NULL'),array('order' => 'published DESC'));
            $f3->set('category',$category);
        } else {
            $posts = $this->Model->Posts->fetchPublished();
        }

        $blogs = $this->Model->map($posts,'user_id','Users');
        $blogs = $this->Model->map($posts,array('post_id','Post_Categories','category_id'),'Categories',false,$blogs);
        $f3->set('blogs',$blogs);
    }

    /** This method displays a single blog post.
     * It retrieved the post ID from the URL parameters and fetches the corresponding post.
     * If the post is not found, it redirects to the home page. 
	 * If the post is not published yet, it adds a status message and redirects the user to the home page.
     * It maps the post to its user and categories and fetches all comments associated with the post. 
     * It then send the resulting data to be used in the view.
     */
    public function view($f3) {
        $id = $f3->get('PARAMS.3');
        if(empty($id)) {
            return $f3->reroute('/');
        }
        $post = $this->Model->Posts->fetch($id);
        if(empty($post)) {
            StatusMessage::add('Post not found','error');
            return $f3->reroute('/');
        }

        if (empty($post->published) || ($this->Auth->user('level') < 2 && $post->published > date('Y-m-d H:i:s'))) {
            StatusMessage::add('This post is not yet published', 'error'); 
            return $f3->reroute('/');
        }
        
        $blog = $this->Model->map($post,'user_id','Users');
        $blog = $this->Model->map($post,array('post_id','Post_Categories','category_id'),'Categories',false,$blog);

        $comments = $this->Model->Comments->fetchAll(array('blog_id' => $id));
        $allcomments = $this->Model->map($comments,'user_id','Users');

        $f3->set('comments',$allcomments);
        $f3->set('blog',$blog);        
    }

    /** This method is used to reset the blog by deleting all posts, categories, comments and mappings.
	 * It checks if the user has administrative privileges and if not, adds a status message and redirects to the home page.
	 * It fetches all posts, categories, comments and mappings and deletes them.
     * After resetting, it adds a status message and redirects to the home page.
     */
    public function reset($f3) {
        // Check if the user has administrative privileges
        if ($this->Auth->user('level') < 2) {
            StatusMessage::add('You do not have permission to reset the blog','danger');
            return $f3->reroute('/');
        }

        $allposts = $this->Model->Posts->fetchAll();
        $allcategories = $this->Model->Categories->fetchAll();
        $allcomments = $this->Model->Comments->fetchAll();
        $allmaps = $this->Model->Post_Categories->fetchAll();
        foreach($allposts as $post) $post->erase();
        foreach($allcategories as $cat) $cat->erase();
        foreach($allcomments as $com) $com->erase();
        foreach($allmaps as $map) $map->erase();
        StatusMessage::add('Blog has been reset');
        return $f3->reroute('/');
    }

    /** Handles adding a new comment to a blog post. 
	 * It retrives the post ID from the URL parameters and fetches the corresponidng post. If the request is a POST, it creates a new comment, copies the data from the POST request
     * and saves it. 
	 * If moderation is enabled, it sets the comment to be moderated. It then adds a status message based on 
     * whether the comment is moderated or not and redirects to the view page of the post.
     */
    public function comment($f3) {
        $id = $f3->get('PARAMS.3');
        $post = $this->Model->Posts->fetch($id);

        
        if($this->request->is('post')) {
            $comment = $this->Model->Comments;
            $comment->copyfrom('POST');

            //Sanitize input
            $comment->subject = !empty($comment->subject) ? h($comment->subject) : 'RE: ' . h($post->title); 
            $comment->message = h($comment->message); 
            $comment->blog_id = $id; 
            $comment->created = mydate();

            //Moderation of comments
            if (!empty($this->Settings['moderate']) && $this->Auth->user('level') < 2) {
                $comment->moderated = 0;
            } else {
                $comment->moderated = 1;
            }

            $comment->save();

            //Redirect
            if($comment->moderated == 0) {
                StatusMessage::add('Your comment has been submitted for moderation and will appear once it has been approved','success');
            } else {
                StatusMessage::add('Your comment has been posted','success');
            }
            return $f3->reroute('/blog/view/' . $id);
        }
    }

    /** Retrieves the comment ID and moderation option from the URL parameters and
     * fetches the corresponding comment. 
	 * It either approves or denies the comment based on the moderation option.
     * It then adds a status message and redirects to the view page of the post.
     */
    public function moderate($f3) {
        list($id,$option) = explode("/",$f3->get('PARAMS.3'));
        $comments = $this->Model->Comments;
        $comment = $comments->fetch($id);

        $post_id = $comment->blog_id;
        //Approve
        if ($option == 1) {
            $comment->moderated = 1;
            $comment->save();
        } else {
        //Deny
            $comment->erase();
        }
        StatusMessage::add('The comment has been moderated');
        $f3->reroute('/blog/view/' . $comment->blog_id);
    }

    /**If the request method is POST, it extracts the search term and sets it to be used in the view.
     * It then performs a search query on the table with posts, allowing the use of wildcards. If no results are found,
     * it adds a status message and redirects to the search page. If results are found, it loads the associated data and sends
     * it to be used in the view.
	 * It sanitizes the search term and normalizes wildcards. 
	 * It then prepares and executes an SQL statement to search for the term in the title and content of the posts.
     */
    public function search($f3) {
        if ($this->request->is('post')) {
            extract($this->request->data);
    
            //Inline HTMLPurifier Configuration
            require_once 'vendor/autoload.php';
    
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', ''); // Disallow all HTML tags
            $config->set('Core.EscapeInvalidTags', true); // Escape invalid tags instead of removing them
            $config->set('URI.DisableExternalResources', true);
            $config->set('URI.DisableResources', true);
    
            $purifier = new HTMLPurifier($config);
    
            //Purify the input
            $search = $purifier->purify($search);
    
            //Normalize wildcards
            $search = str_replace("*", "%", $search); // Allow * as wildcard
    
            //Pass the sanitized search term to the view
            $f3->set('search', htmlspecialchars($search, ENT_QUOTES, 'UTF-8'));
    
            //Prepare and execute SQL statement
            $stmt = $this->db->connection->prepare(
                "SELECT id FROM `posts` WHERE `title` LIKE ? OR `content` LIKE ?"
            );
            $stmt->execute(["%$search%", "%$search%"]);
    
            //Fetch the results
            $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ids = array_column($ids, 'id');
    
            if (empty($ids)) {
                StatusMessage::add('No search results found for ' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8'));
                return $f3->reroute('/blog/search');
            }
    
            //Load associated data
            $posts = $this->Model->Posts->fetchAll(['id' => $ids]);
            $blogs = $this->Model->map($posts, 'user_id', 'Users');
            $blogs = $this->Model->map($posts, ['post_id', 'Post_Categories', 'category_id'], 'Categories', false, $blogs);
    
            // Set data for the view
            $f3->set('blogs', $blogs);
            $this->action = 'results';
        }
    }
}
?>
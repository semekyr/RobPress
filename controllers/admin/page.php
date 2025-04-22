<?php

namespace Admin;

class Page extends AdminController {

    public function index($f3) {
        $pages = $this->Model->Pages->fetchAll();
        $f3->set('pages', $pages);
    }

    public function add($f3) {
        if ($this->request->is('post')) {
            require_once 'vendor/autoload.php';
    
            //Configure HTMLPurifier
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'b,strong,i,em,a[href|target],p,ul,ol,li,img[src|alt|width|height]');
            $config->set('URI.DisableExternalResources', true);
            $config->set('URI.DisableResources', true);
            $purifier = new \HTMLPurifier($config);
    
            //Retrieve and sanitize title
            $title = $purifier->purify($this->request->data['title'] ?? '');
            $pagename = strtolower(str_replace(" ", "_", $title));
    
            //Validate title
            if (empty($title)) {
                \StatusMessage::add('Page title cannot be blank', 'danger');
                return $f3->reroute('/admin/page');
            }
    
            //Attempt to create page
            if ($this->Model->Pages->create($pagename)) {
                \StatusMessage::add('Page created successfully', 'success');
            } else {
                \StatusMessage::add('Failed to create page', 'danger');
            }
    
            //Redirect to edit page
            return $f3->reroute('/admin/page/edit/' . htmlspecialchars($pagename, ENT_QUOTES, 'UTF-8'));
        }
    }
    
    public function edit($f3) {
        $pagename = $f3->get('PARAMS.3');
    
        if ($this->request->is('post')) {
            require_once 'vendor/autoload.php';
    
            //Configure HTMLPurifier
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'b,strong,i,em,a[href|target],p,ul,ol,li,img[src|alt|width|height]');
            $config->set('URI.DisableExternalResources', true);
            $config->set('URI.DisableResources', true);
            $purifier = new \HTMLPurifier($config);
    
            $pages = $this->Model->Pages;
            $pages->title = $pagename;
    
            //Sanitize and validate content
            $content = $purifier->purify($this->request->data['content'] ?? '');
            $pages->content = $content;
    
            if (empty($content)) {
                \StatusMessage::add('Page content cannot be blank', 'danger');
                return $f3->reroute('/admin/page/edit/' . htmlspecialchars($pagename, ENT_QUOTES, 'UTF-8'));
            }
    
            //Attempt to save page
            if ($pages->save()) {
                \StatusMessage::add('Page updated successfully', 'success');
            } else {
                \StatusMessage::add('Failed to update page', 'danger');
            }
    
            return $f3->reroute('/admin/page');
        }
    
        //Retrieve and sanitize page data for the view
        $pagetitle = ucfirst(str_replace("_", " ", str_ireplace(".html", "", $pagename)));
        $page = $this->Model->Pages->fetch($pagename);
    
        if ($page === false) {
            \StatusMessage::add('Page does not exist', 'danger');
            return $f3->reroute('/admin/page');
        }
    
        $f3->set('pagetitle', htmlspecialchars($pagetitle, ENT_QUOTES, 'UTF-8'));
        $f3->set('page', $page);
    }
    

    public function delete($f3) {
        $pagename = $f3->get('PARAMS.3');
    
        //Check if page name is provided
        if (empty($pagename)) {
            \StatusMessage::add('Page name not specified', 'danger');
            return $f3->reroute('/admin/page');
        }

        error_log ("Page name: " . $pagename);
        error_log ("Type of page name: " . gettype($pagename));
    
        //Fetch the page
        $page = $this->Model->Pages->delete($pagename);
        //Check if page exists
        if (!$page) {
            \StatusMessage::add('Page does not exist', 'danger');
            return $f3->reroute('/admin/page');
        }
        \StatusMessage::add('Page deleted successfully', 'success');
        return $f3->reroute('/admin/page');
    }
    
    
}
    

?>
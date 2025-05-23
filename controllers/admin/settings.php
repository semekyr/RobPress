<?php

namespace Admin;

class Settings extends AdminController {

    public function index($f3) {
        $settings = $this->Model->Settings->fetchAll();

        if ($this->request->is('post')) {
            foreach ($settings as $setting) {
                if (isset($this->request->data[$setting->setting])) {
                    $sanitizedval = h($this->request->data[$setting->setting]);

                    //Update the setting value
                    $setting->value = $sanitizedval;
                    $setting->save();
                } else {
                    $setting->value = 0;
                    $setting->save();
                }
            }
            \StatusMessage::add('Settings updated', 'success');
        }
        $f3->set('settings', $settings);
    }

    public function clearcache($f3) {
        $cache = isset($this->request->data['cache']) ? getcwd() . '/' . $this->request->data['cache'] : getcwd() . '/tmp/cache';
        $cache = str_replace(".", "", $cache);
        $this->delTree($cache);
    }

    public function delTree($dir) { 
        $files = array_diff(scandir($dir), array('.', '..')); 
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ? $this->delTree("$dir/$file") : unlink("$dir/$file"); 
        }
        return rmdir($dir); 
    } 

}

?>
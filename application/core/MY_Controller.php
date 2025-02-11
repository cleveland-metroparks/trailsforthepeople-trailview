<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Extend CI core Controller class to create a base page template for backend pages.
 */
class MY_Controller extends CI_Controller {

public $loggedin;
private $js_scripts = array();
private $user_messages = array();

function __construct()
{
    parent::__construct();

    // Add our common JS as a <script> include.
    $this->_add_js_include('static/dist/js/constants-backend-old.min.js');

    // Shortcuts for use in templates:
    // An associated array of the Contributor's info (and quick test for whether they're logged-in).
    $this->loggedin = $this->session->userdata('contributor');
}

function _output($content)
{
    // Load the base template with output content available as $content
    $data['content'] = &$content;
    $data['messages'] = $this->_get_user_messages_markup();
    $data['js_includes'] = $this->_render_js_includes();
    $data['mainmenu_left'] = $this->_generate_menu_markup($this->_mainmenu_left_array(), $this->uri->uri_string);
    $data['mainmenu_right'] = $this->_generate_menu_markup($this->_mainmenu_right_array(), $this->uri->uri_string);
    $body_classes_array = array(
      'section-' . $this->router->class,
      'page-' . $this->router->method,
    );
    $data['body_classes'] = implode(' ', $body_classes_array);

    echo($this->load->view('backend_base', $data, true));
}

/*
 * Check that we're in SSL mode and that the user is logged-in.
 *
 * @param $check_admin:
 *   Whether to also ensure the user is an administrator.
 *
 * return:
 *   NULL if everything checks-out.
 *   Otherwise redirect or load appropriate page.
 */
protected function _user_access($area='') {
    $user = $this->session->userdata('contributor');
    // Must be logged-in
    if (!$user) {
        return redirect(ssl_url('administration/login'));
    }
    // Check if user has access to area. Admin overrides all areas.
    if (!empty($area)) {
        if (!$user['admin'] && !$user[$area]) {
            return redirect(ssl_url('administration/access_denied'));
        }
    }
}

/**
 * Add a JavaScript external (but local, relative) script include to the HTML <head>.
 */
protected function _add_js_include($file) {
    $this->js_scripts[] = $file;
}

/**
 *
 */
private function _render_js_includes() {
    $markup = '';
    foreach ($this->js_scripts as $script) {
        $markup .= '<script type="text/javascript" src="' . ssl_url($script) . '"></script>' . "\n";
    }
    return $markup;
}

/**
 * Turn all of the user messages into HTML markup.
 */
protected function _get_user_messages_markup() {
    $markup = '';
    $valid_statuses = array('success', 'info', 'warning', 'danger');
    foreach ($this->user_messages as $message) {
        if (!in_array($message['status'], $valid_statuses)) {
            $message['status'] = 'info';
        }
        $markup .= '<div class="alert alert-' . $message['status'] . '" role="alert">' . $message['text'] . '</div>';
    }
    return $markup;
}

/**
 * Add a message to be displayed to the user in the messages section of the page.
 */
protected function _add_user_message($message_text, $status='info') {
    $this->user_messages[] = array(
        'text' => $message_text,
        'status' => $status
    );
}

/**
 * Clear the user messages.
 */
protected function _clear_user_messages($message) {
    $this->user_messages = array();
}

/*
 * From our custom menu array, generate markup to display it as a bootstrap menu.
 *
 * Control access to menu items if specified with the item in the array,
 * checking user access stored in session.
 *
 * @see _mainmenu_left_array()
 * @see _mainmenu_right_array()
 */
private function _generate_menu_markup($menu, $active_path='') {
  // Only logged-in users have access to the menu.
  $user = $this->session->userdata('contributor');
  if (!$user) {
    return;
  }

  $markup = '';

  foreach ($menu as $item) {

    // Check if the menu item has access control restrictions.
    if (isset($item['access'])) {
      // Ensure user isn't admin (if they are, don't worry about specific area check)
      if (!isset($user['admin']) || !$user['admin']) {
        // Check access to area
        if (!isset($user[$item['access']]) || !$user[$item['access']]) {
          // Access denied.
          // Skip this menu item and move on to the next.
          //
          // Note that [currently] if access is denied to an item with a submenu,
          // we skip displaying the whole submenu too.
          continue;
        }
      }
    }

    $classes = array();
    if (isset($item['url']) && $item['url'] == $active_path) {
      $classes[] = 'active';
    }
    if (isset($item['submenu'])) {
      $classes[] = 'dropdown';
    }

    $classes_str = ' class="';
    $i=0;
    foreach ($classes as $class) {
      $classes_str .= ($i++ > 0) ? ' ' : '';
      $classes_str .= $class;
    }
    $classes_str .= '"';

    $markup .= '<li' . $classes_str . '>';

    if (isset($item['submenu'])) {
      if (isset($item['url'])) {
        $markup .= '<a href="' . ssl_url($item['url']) . '" role="button" aria-haspopup="true" aria-expanded="true">' . $item['title'] . ' <span class="caret"></span></a>';
      } else {
        $markup .= '<a href="#" role="button" aria-haspopup="true" aria-expanded="true">' . $item['title'] . ' <span class="caret"></span></a>';
        //$markup .= $item['title'] . ' <span class="caret"></span></a>';
      }
      $markup .= '<ul class="dropdown-menu">';
      $markup .= $this->_generate_menu_markup($item['submenu'], $active_path);
      $markup .= '</ul>';
    } else {
      $markup .= '<a href="' . ssl_url($item['url']) . '">' . $item['title'] . '</a>';
    }
    $markup .= '</li>' . "\n";
  }
  return $markup;
}

/*
 * Return our back-end navigation main menu right side as an array.
 *
 * @see _generate_menu_markup()
 */
private function _mainmenu_right_array() {
  $menu = array(
    /*
     * User Account
     */
    array(
      'url' => 'contributors/user',
      'title' => 'User Account'
    ),
    /*
     * Log out
     */
    array(
      'url' => 'administration/logout',
      'title' => 'Log Out'
    ),
  );
  return $menu;
}

/*
 * Return our back-end navigation main menu left side as an array.
 *
 * @see _generate_menu_markup()
 */
private function _mainmenu_left_array() {
  $menu = array(
    /*
     * Contributors
     */
    array(
      'url' => 'contributors',
      'title' => 'Contributors',
      'submenu' => array(
        array(
          'url' => 'contributors/markers',
          'title' => 'Markers',
          'access' => 'allow_markers',
        ),
        array(
          'url' => 'contributors/loops',
          'title' => 'Loops',
          'access' => 'allow_loops',
        ),
      ),
    ),
    /*
     * Administration
     */
    array(
      'url' => 'administration',
      'title' => 'Administration',
      'access' => 'admin',
      'submenu' => array(
        array(
          'url' => 'administration/contributors',
          'title' => 'Manage Contributors',
          'access' => 'admin',
        ),
        array(
          'url' => 'administration/hint_maps',
          'title' => 'Hint Maps',
          'access' => 'admin',
        ),
        array(
          'url' => 'administration/auditlog',
          'title' => 'Log',
          'access' => 'admin',
        ),
      ),
    ),
  );

  return $menu;
}

}
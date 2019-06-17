<?php
/*
 * Plugin Name: My plugin
 * Description: Demonstrates concurrency issues around wp_insert_post
 * Author: Dominic Slee
 */

class Demonstrata {
  private $child_ready, $parent_ready, $parent_finish, $child_finish;
  private $is_parent = false;
  const POST_NAME = 'test_post';
  public function __construct() {
    $this->child_ready = sem_get(55);
    $this->parent_ready = sem_get(56);
    $this->parent_finish = sem_get(57);
    $this->child_finish = sem_get(58);
  }

  public function run() {
    wp_cache_flush();
    global $wpdb;
    $post_name = self::POST_NAME;
    $post_ids = $wpdb->get_col("SELECT DISTINCT ID FROM $wpdb->posts WHERE post_name LIKE '%$post_name%'");
    foreach ($post_ids as $post_id) {
      wp_delete_post($post_id, true);
      WP_CLI::line("Deleting post $post_id...");
      sleep(0.3);
    }
    $pid = pcntl_fork();
    if ($pid == -1) {
      throw new Exception("Could not fork");
    }
    $this->is_parent = $pid != 0;
    add_filter( 'wp_insert_post_data', array($this, 'wp_insert_post_data'), 2, 10);
    #add_filter( 'wp_insert_post_empty_content', array($this, 'handle_post_empty_content'), 2, 10);
    if ($this->is_parent) {
      $this->run_parent($pid);
    } else {
      $this->run_child();
    }
  }

  public function handle_post_empty_content($maybe_empty, $postarr) {
    $this->blocker();
    return $maybe_empty;
  }

  public function wp_insert_post_data($data, $postarr) {
    $this->blocker();
    return $data;
  }

  public function blocker() {
    if ($this->is_parent) {
      sem_release($this->parent_ready);
      sem_acquire($this->child_ready); // wait until child is ready
    } else {
      sem_release($this->child_ready);
      sem_acquire($this->parent_ready); // wait until parent is ready
      sem_acquire($this->parent_finish); // wait until parent finished
      sleep(0.5);
    }
  }

  public function run_parent($pid) {
    sem_acquire($this->parent_ready); // release when ready (in hook)
    sem_acquire($this->parent_finish); // release when finish
    sleep(1);
    $this->insert_post(true);
    sleep(1);
    sem_release($this->parent_finish);
    sem_acquire($this->child_finish);
    $killed = posix_kill( $pid, SIGKILL );
    if (!$killed) {
      throw new Exception("not killed");
    }
  }

  public function run_child() {
    sem_acquire($this->child_ready);
    sem_acquire($this->child_finish); 
    sleep(5);
    $this->insert_post(false);
    sem_release($this->child_finish);
    sleep(500);
  }

  public function insert_post($is_parent) {
    $my_post = [
      'post_title'    => 'My post',
      'post_content'  => 'This is my post.',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_category' => array( 8,39 ),
      'post_name'     => self::POST_NAME
    ];
    $name = $is_parent ? "PARENT" : "CHILD";
    $post_id = wp_insert_post($my_post, true);
    sleep(0.5);
    if (empty($post_id)) {
      return \WP_CLI::error("failed inserting post in $name");
    }
    $post = get_post($post_id);
    sleep(0.5);
    if (empty($post)) {
      return \WP_CLI::error("couldn't get post $post_id in $name");
    }
    $post_name = $post->post_name;
    \WP_CLI::line("$name inserted as $post_id: $post_name");
    wp_cache_flush();
  }
}

$my_cmd = function( $args ) {
	$d = new Demonstrata();
  $d->run();
};
WP_CLI::add_command( 'my_plugin', $my_cmd );



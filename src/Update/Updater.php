<?php
declare(strict_types=1);
namespace Postebase\Update;
// Credit to https://github.com/pablolopezmestre/wordpress-github-plugin-updater
/**
 * Update WordPress plugin from GitHub Private Repository.
 */
class Updater
{
  private $file;
  private $plugin_data;
  private $basename;
  private $active = false;
  private $github_response;

  public function __construct($file)
  {
    define("GH_REQUEST_URI", "https://api.github.com/repos/%s/%s/releases");
    define("GHPU_USERNAME", "wpuipress");
    define("GHPU_REPOSITORY", "postebase-plugin");

    $this->file = $file;
    $this->basename = plugin_basename($this->file);
  }

  /**
   * Init GitHub Plugin Updater.
   */
  public function init(): void
  {
    add_filter("pre_set_site_transient_update_plugins", [$this, "modify_transient"], 10, 1);
    add_filter("plugins_api", [$this, "plugin_popup"], 10, 3);
    add_filter("upgrader_post_install", [$this, "after_install"], 10, 3);
  }

  /**
   * If new version exists, update transient with GitHub info.
   *
   * @param object $transient Transient object with plugins information.
   */
  public function modify_transient(object $transient): object
  {
    if (!property_exists($transient, "checked")) {
      return $transient;
    }

    $this->get_repository_info();
    $this->get_plugin_data();

    if (version_compare($this->github_response["tag_name"], $transient->checked[$this->basename], "gt")) {
      $plugin = [
        "url" => $this->plugin_data["PluginURI"],
        "slug" => current(explode("/", $this->basename)),
        "package" => $this->github_response["zipball_url"],
        "new_version" => $this->github_response["tag_name"],
      ];

      $transient->response[$this->basename] = (object) $plugin;
    }

    return $transient;
  }

  /**
   * Complete details of new plugin version on popup.
   *
   * @param array|false|object $result The result object or array. Default false.
   * @param string             $action The type of information being requested from the Plugin Installation API.
   * @param object             $args   Plugin API arguments.
   */
  public function plugin_popup(bool $result, string $action, object $args)
  {
    if ("plugin_information" !== $action || empty($args->slug)) {
      return false;
    }

    if ($args->slug == current(explode("/", $this->basename))) {
      $this->get_repository_info();
      $this->get_plugin_data();

      $plugin = [
        "name" => $this->plugin_data["Name"],
        "slug" => $this->basename,
        "requires" => $this->plugin_data["RequiresWP"],
        "tested" => $this->plugin_data["TestedUpTo"],
        "version" => $this->github_response["tag_name"],
        "author" => $this->plugin_data["AuthorName"],
        "author_profile" => $this->plugin_data["AuthorURI"],
        "last_updated" => $this->github_response["published_at"],
        "homepage" => $this->plugin_data["PluginURI"],
        "short_description" => $this->plugin_data["Description"],
        "sections" => [
          "Description" => $this->plugin_data["Description"],
          "Updates" => $this->github_response["body"],
        ],
        "download_link" => $this->github_response["zipball_url"],
      ];

      return (object) $plugin;
    }

    return $result;
  }

  /**
   * Active plugin after install new version.
   *
   * @param bool  $response   Installation response.
   * @param array $hook_extra Extra arguments passed to hooked filters.
   * @param array $result     Installation result data.
   */
  public function after_install($response, $hook_extra, $result)
  {
    return;
    // Only run this hook for the postebase plugin
    if (!isset($result["destination_name"]) || strpos($result["destination_name"], "postebase") == false) {
      return $result;
    }

    global $wp_filesystem;

    // Define the new install directory, ensure it ends with the desired folder name
    $pluginName = plugin_basename($this->file);
    $nameParts = explode(",", $pluginName);
    $desired_install_directory = WP_PLUGIN_DIR . "/postebase";

    if (!is_dir($desired_install_directory)) {
      wp_mkdir_p($desired_install_directory);
    }

    if (!is_writable($desired_install_directory)) {
      error_log("Installation failed: Directory is not writable.");
      return $result; // Handle error appropriately.
    }

    // Check if the destination directory already exists, if so, we'll want to clear it out
    if ($wp_filesystem->is_dir($desired_install_directory)) {
      $wp_filesystem->delete($desired_install_directory, true);
    }

    // Now, move the uploaded plugin to the desired directory
    $move_result = $wp_filesystem->move($result["destination"], $desired_install_directory);

    // Make sure the move was successful
    if ($move_result) {
      $result["destination"] = $desired_install_directory;
      $this->file = $desired_install_directory . "/" . basename($this->file);

      // If the plugin was active, reactivate it
      if ($this->active) {
        activate_plugin(plugin_basename($this->file));
      }
    } else {
      // Handle error; move operation failed
      return $result;
    }

    return $response;
  }

  /**
   * Gets repository data from GitHub.
   */
  private function get_repository_info(): void
  {
    if (null !== $this->github_response) {
      return;
    }

    $args = [
      "method" => "GET",
      "timeout" => 5,
      "redirection" => 5,
      "httpversion" => "1.0",
      "sslverify" => true,
    ];
    $request_uri = sprintf(GH_REQUEST_URI, GHPU_USERNAME, GHPU_REPOSITORY);

    $request = wp_remote_get($request_uri, $args);

    if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
      // Handle error; maybe set a transient to retry later
      return;
    }

    $response = json_decode(wp_remote_retrieve_body($request), true);

    if (is_array($response)) {
      $response = current($response);
    }

    $this->github_response = $response;
  }

  /**
   * Gets plugin data.
   */
  private function get_plugin_data(): void
  {
    if (null !== $this->plugin_data) {
      return;
    }

    $this->plugin_data = get_plugin_data($this->file);
  }
}

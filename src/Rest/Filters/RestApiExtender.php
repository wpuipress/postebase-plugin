<?php
namespace Postebase\Rest\Filters;
// If this file is called directly, abort.
!defined("ABSPATH") ? exit() : "";

/**
 * Class to add custom properties to REST API responses for all public post types.
 */
class RestApiExtender
{
  /**
   * Initializes the process by hooking into WordPress actions.
   */
  public function __construct()
  {
    add_action("init", [$this, "register_custom_properties"]);
  }

  /**
   * Registers custom properties for all public post types.
   */
  public function register_custom_properties()
  {
    $post_types = get_post_types(["public" => true], "objects");

    foreach ($post_types as $post_type) {
      add_filter("rest_prepare_" . $post_type->name, [$this, "add_custom_property_to_response"], 10, 3);
    }
  }

  /**
   * Checks if the current request is authenticated using an Application Password.
   *
   * @return bool True if authenticated via App Password, false otherwise.
   */
  private function is_authenticated_via_app_password()
  {
    // WordPress 5.6 and above stores authentication method in the current user's WP_User object
    $current_user = wp_get_current_user();

    if (!empty($current_user) && !empty($current_user->ID)) {
      // Check for the application password used for authentication
      $app_password_used = get_user_meta($current_user->ID, "wp_application_passwords_last_used", true);
      return !empty($app_password_used);
    }

    return false;
  }

  /**
   * Adds a custom property to the REST API response.
   *
   * @param WP_REST_Response $response The response object.
   * @param WP_Post          $post     Post object.
   * @param WP_REST_Request  $request  Request object.
   * @return WP_REST_Response Modified response object.
   */
  public function add_custom_property_to_response($response, $post, $request)
  {
    // Check if 'postebase' query param exists and the request is authenticated via App Password
    if ($request->get_param("postebase") && $this->is_authenticated_via_app_password()) {
      // Modify the response only if both conditions are true
      $response->data["custom_property"] = "This is my custom value";
    }

    return $response;
  }
}

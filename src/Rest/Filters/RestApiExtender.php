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
   * Adds a custom property to the REST API response.
   *
   * @param WP_REST_Response $response The response object.
   * @param WP_Post          $post     Post object.
   * @param WP_REST_Request  $request  Request object.
   * @return WP_REST_Response Modified response object.
   */
  public function add_custom_property_to_response($response, $post, $request)
  {
    // You can modify the value or add conditions based on the $post or $request
    $response->data["custom_property"] = "This is my custom value";

    return $response;
  }
}

// Initialize the class to apply the modifications.
new RestApiExtender();

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
   * Retrieves all ACF fields associated with a specified post type.
   *
   * This function fetches all field groups associated with a given post type
   * and then retrieves all fields within each group, returning a comprehensive
   * list of fields and their configurations.
   *
   * @param string $post_type The slug of the post type for which to retrieve ACF fields.
   * @return array An array of ACF field configurations associated with the specified post type.
   */
  private function get_acf_fields_by_post_type($post_type)
  {
    $field_groups = acf_get_field_groups(["post_type" => $post_type]);
    $all_fields = [];

    foreach ($field_groups as $group) {
      $fields = acf_get_fields($group["key"]);
      if ($fields) {
        foreach ($fields as $field) {
          // Add each field's settings to the all_fields array
          $all_fields[] = $field;
        }
      }
    }

    return $all_fields;
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
    //$response->data["custom_request"] = $request->get_param("postebase");
    // Check if 'postebase' query param exists and the request is authenticated via App Password
    if (!$request->get_param("postebase") || !is_user_logged_in()) {
      return $response;
    }

    // Modify the response only if both conditions are true
    //$response->data["custom_property"] = "This is my custom value";

    if (function_exists("get_field_objects")) {
      $fields = get_field_objects();
      $response->data["postebase"] = [
        "acf" => $this->get_acf_fields_by_post_type($post->post_type),
      ];
    }

    return $response;
  }
}
